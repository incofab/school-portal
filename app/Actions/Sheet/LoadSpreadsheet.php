<?php

namespace App\Actions\Sheet;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class LoadSpreadsheet
{
  public static function fromUploadedFile(UploadedFile $file): Spreadsheet
  {
    return self::fromPath($file->getRealPath());
  }

  public static function fromPath(string $path): Spreadsheet
  {
    $previousHandler = set_error_handler(static function (
      int $severity,
      string $message,
      string $file,
      int $line
    ) use (&$previousHandler): bool {
      if (
        $severity === E_WARNING &&
        str_starts_with(
          $message,
          'file_exists(): open_basedir restriction in effect. File(/xl/'
        )
      ) {
        return true;
      }

      if (is_callable($previousHandler)) {
        return (bool) $previousHandler($severity, $message, $file, $line);
      }

      return false;
    });

    try {
      return IOFactory::load($path);
    } finally {
      restore_error_handler();
    }
  }
}
