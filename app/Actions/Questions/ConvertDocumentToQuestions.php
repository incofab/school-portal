<?php

namespace App\Actions\Questions;

use Illuminate\Http\UploadedFile;

class ConvertDocumentToQuestions
{
  public function __construct(private UploadedFile $file)
  {
  }

  public function run(): array
  {
    $content = (new ExtractDocumentContent($this->file))->run();
    if ($content === '') {
      throw new \RuntimeException('The document has no readable content');
    }

    return (new ConvertTextToQuestions())->run($content);
  }
}
