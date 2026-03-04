<?php

namespace App\Actions\Questions;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class ExtractDocumentContent
{
  public function __construct(private UploadedFile $file)
  {
  }

  public function run(): string
  {
    $extension = strtolower($this->file->getClientOriginalExtension() ?? '');
    if ($extension === 'txt') {
      return $this->readTextFile();
    }
    if ($extension === 'doc') {
      return $this->readDocFile();
    }
    if ($extension === 'docx') {
      return $this->readDocxFile();
    }

    throw new \RuntimeException('Unsupported document type');
  }

  private function readTextFile(): string
  {
    $content = file_get_contents($this->file->getRealPath());
    return trim($content ?: '');
  }

  private function readDocxFile(): string
  {
    $zip = new ZipArchive();
    if ($zip->open($this->file->getRealPath()) !== true) {
      throw new \RuntimeException('Unable to open the document');
    }

    $documentXml = $zip->getFromName('word/document.xml');
    if (!$documentXml) {
      $zip->close();
      throw new \RuntimeException('Invalid Word document');
    }

    $relsXml = $zip->getFromName('word/_rels/document.xml.rels') ?: '';
    $imagesByRelId = $this->buildImageMap($zip, $relsXml);

    $text = $this->extractTextFromDocumentXml($documentXml, $imagesByRelId);

    $zip->close();

    return trim($text);
  }

  private function readDocFile(): string
  {
    $path = $this->file->getRealPath();
    $text = $this->convertDocToText($path);
    if (trim($text) === '') {
      throw new \RuntimeException('Unable to extract text from the document');
    }
    return trim($text);
  }

  private function convertDocToText(string $path): string
  {
    if ($this->canRunCommand('/usr/bin/textutil')) {
      $process = new Process(['/usr/bin/textutil', '-convert', 'txt', '-stdout', $path]);
      $process->run();
      if ($process->isSuccessful()) {
        return $process->getOutput();
      }
    }

    $soffice = $this->findCommand('soffice');
    if ($soffice) {
      $tmpDir = sys_get_temp_dir() . '/doc-convert-' . bin2hex(random_bytes(6));
      if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0700, true);
      }
      $process = new Process([
        $soffice,
        '--headless',
        '--convert-to',
        'txt:Text',
        '--outdir',
        $tmpDir,
        $path
      ]);
      $process->run();
      if ($process->isSuccessful()) {
        $converted = $tmpDir . '/' . pathinfo($path, PATHINFO_FILENAME) . '.txt';
        if (file_exists($converted)) {
          $content = file_get_contents($converted);
          @unlink($converted);
          @rmdir($tmpDir);
          return $content ?: '';
        }
      }
    }

    throw new \RuntimeException(
      'No DOC converter found. Install textutil (macOS) or LibreOffice (soffice).'
    );
  }

  private function canRunCommand(string $path): bool
  {
    return is_file($path) && is_executable($path);
  }

  private function findCommand(string $command): ?string
  {
    $process = new Process(['which', $command]);
    $process->run();
    if (!$process->isSuccessful()) {
      return null;
    }
    $resolved = trim($process->getOutput());
    return $resolved !== '' ? $resolved : null;
  }

  private function buildImageMap(ZipArchive $zip, string $relsXml): array
  {
    if ($relsXml === '') {
      return [];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadXML($relsXml);

    $map = [];
    foreach ($dom->getElementsByTagName('Relationship') as $relationship) {
      $type = $relationship->getAttribute('Type');
      if (!str_contains($type, '/image')) {
        continue;
      }
      $relId = $relationship->getAttribute('Id');
      $target = ltrim($relationship->getAttribute('Target'), '/');
      $path = 'word/' . $target;
      $imageBinary = $zip->getFromName($path);
      if ($imageBinary === false) {
        continue;
      }
      $mime = $this->mimeFromPath($target);
      $map[$relId] = 'data:' . $mime . ';base64,' . base64_encode($imageBinary);
    }

    return $map;
  }

  private function extractTextFromDocumentXml(
    string $documentXml,
    array $imagesByRelId
  ): string {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadXML($documentXml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace(
      'w',
      'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
    );
    $xpath->registerNamespace(
      'a',
      'http://schemas.openxmlformats.org/drawingml/2006/main'
    );
    $xpath->registerNamespace(
      'r',
      'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
    );

    $text = '';
    $paragraphs = $xpath->query('//w:document//w:body//w:p');
    foreach ($paragraphs as $paragraph) {
      $buffer = '';
      $nodes = $xpath->query(
        './/w:t|.//w:tab|.//w:br|.//w:drawing',
        $paragraph
      );

      foreach ($nodes as $node) {
        if ($node->localName === 't') {
          $buffer .= $node->nodeValue;
        } elseif ($node->localName === 'tab') {
          $buffer .= "\t";
        } elseif ($node->localName === 'br') {
          $buffer .= "\n";
        } elseif ($node->localName === 'drawing') {
          $blip = $xpath->query('.//a:blip', $node)->item(0);
          $embed = $blip?->getAttributeNS(
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
            'embed'
          );
          if ($embed && isset($imagesByRelId[$embed])) {
            $buffer .= '[IMAGE:' . $imagesByRelId[$embed] . ']';
          } else {
            $buffer .= '[IMAGE]';
          }
        }
      }

      if (trim($buffer) !== '') {
        $text .= $buffer . "\n";
      }
    }

    return $text;
  }

  private function mimeFromPath(string $path): string
  {
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($extension) {
      'png' => 'image/png',
      'jpg', 'jpeg' => 'image/jpeg',
      'gif' => 'image/gif',
      'svg' => 'image/svg+xml',
      'webp' => 'image/webp',
      default => 'application/octet-stream'
    };
  }
}
