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

    $prompt = $this->buildPrompt($content);
    $aiRes = initPrism(
      'You are a well qualified school teacher and expert at formatting exam questions.'
    )
      ->withPrompt($prompt)
      ->asText();

    $raw = trimAiResponse($aiRes->text ?? '');
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
      throw new \RuntimeException('AI response was not valid JSON');
    }

    return $this->normalizeQuestions($decoded);
  }

  private function buildPrompt(string $content): string
  {
    return "Analyze the following teacher-provided questions content and convert it into a valid JSON array of objects. 
Each object must include these keys: question_no, question, option_a, option_b, option_c, option_d, option_e, answer, answer_meta.
Use HTML for question, options, and answer_meta (explanations). Preserve the original meaning and formatting.
If the content includes images marked as [IMAGE:data:*], replace each with an <img> tag using the same data URI.
For math or chemical symbols, use MathType-compatible HTML (MathML or equivalent) to render them accurately.
Return only valid JSON (double quotes, no comments, no markdown).

Output format example:
[
  { 
    \"question_no\": 1,
    \"question\": \"<p>...</p>\",
    \"option_a\": \"<p>...</p>\",
    \"option_b\": \"<p>...</p>\",
    \"option_c\": \"<p>...</p>\",
    \"option_d\": \"<p>...</p>\",
    \"option_e\": null,
    \"answer\": \"A\",
    \"answer_meta\": \"<p>...</p>\"
  }
]

Here is the content to analyze:
{$content}";
  }

  private function normalizeQuestions(array $questions): array
  {
    $normalized = [];
    $nextQuestionNo = 1;

    foreach ($questions as $item) {
      if (!is_array($item)) {
        continue;
      }

      $questionNo = intval($item['question_no'] ?? $nextQuestionNo);
      $nextQuestionNo = $questionNo + 1;

      $answer = strtoupper(trim($item['answer'] ?? ''));
      if (str_starts_with($answer, 'OPTION_')) {
        $answer = substr($answer, 7, 1);
      }
      if (preg_match('/^[A-E]/', $answer, $match)) {
        $answer = $match[0];
      }

      $normalized[] = [
        'question_no' => $questionNo,
        'question' => $item['question'] ?? '',
        'option_a' => $item['option_a'] ?? '',
        'option_b' => $item['option_b'] ?? '',
        'option_c' => $item['option_c'] ?? null,
        'option_d' => $item['option_d'] ?? null,
        'option_e' => $item['option_e'] ?? null,
        'answer' => $answer,
        'answer_meta' => $item['answer_meta'] ?? ($item['explanation'] ?? null)
      ];
    }

    return $normalized;
  }
}
