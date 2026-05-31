<?php

namespace App\Actions\CourseResult;

use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\TermResult;
use Illuminate\Support\Collection;

class GenerateAiTermResultComments
{
  public function run(ClassResultInfo $classResultInfo): int
  {
    $termResults = TermResult::query()
      ->where('institution_id', $classResultInfo->institution_id)
      ->where('classification_id', $classResultInfo->classification_id)
      ->where('academic_session_id', $classResultInfo->academic_session_id)
      ->where('term', $classResultInfo->term)
      ->where('for_mid_term', $classResultInfo->for_mid_term)
      ->where(function ($query) {
        $query
          ->whereNull('teacher_comment')
          ->orWhere('teacher_comment', '')
          ->orWhereNull('principal_comment')
          ->orWhere('principal_comment', '');
      })
      ->with('student.user', 'academicSession')
      ->get();

    $updatedCount = 0;
    $termResults
      ->chunk(10)
      ->each(function (Collection $chunk) use (
        $classResultInfo,
        &$updatedCount
      ) {
        $payload = $this->buildPayload($classResultInfo, $chunk);
        if (empty($payload)) {
          return;
        }

        $comments = $this->generateComments($payload);
        $updatedCount += $this->applyComments($chunk, $comments);
      });

    return $updatedCount;
  }

  protected function generateComments(array $payload): array
  {
    $prompt = $this->buildPrompt($payload);
    $aiRes = initPrism(
      'You are an experienced school teacher and principal writing student result comments.'
    )
      ->withPrompt($prompt)
      ->asText();

    $raw = trimAiResponse($aiRes->text ?? '');
    $decoded = json_decode($raw, true);

    if (isset($decoded['comments']) && is_array($decoded['comments'])) {
      return $decoded['comments'];
    }

    return is_array($decoded) ? $decoded : [];
  }

  private function buildPayload(
    ClassResultInfo $classResultInfo,
    Collection $termResults
  ): array {
    $studentIds = $termResults->pluck('student_id')->all();
    $courseResults = CourseResult::query()
      ->where('institution_id', $classResultInfo->institution_id)
      ->whereIn('student_id', $studentIds)
      ->with('course:id,title,code', 'academicSession:id,title')
      ->oldest('academic_session_id')
      ->oldest('term')
      ->get()
      ->groupBy('student_id');

    return $termResults
      ->map(function (TermResult $termResult) use ($courseResults) {
        return [
          'term_result_id' => $termResult->id,
          'student' => [
            'id' => $termResult->student_id,
            'name' => $termResult->student?->user?->full_name
          ],
          'current_result' => [
            'academic_session' => $termResult->academicSession?->title,
            'term' => $termResult->term?->value ?? $termResult->term,
            'for_mid_term' => $termResult->for_mid_term,
            'total_score' => $termResult->total_score,
            'average' => $termResult->average,
            'position' => $termResult->position
          ],
          'existing_comments' => [
            'teacher_comment' => $termResult->teacher_comment,
            'principal_comment' => $termResult->principal_comment
          ],
          'course_results_history' => (
            $courseResults[$termResult->student_id] ?? collect()
          )
            ->map(
              fn(CourseResult $courseResult) => [
                'subject' => $courseResult->course?->title,
                'subject_code' => $courseResult->course?->code,
                'score' => $courseResult->result,
                'exam' => $courseResult->exam,
                'grade' => $courseResult->grade,
                'remark' => $courseResult->remark,
                'academic_session' => $courseResult->academicSession?->title,
                'term' => $courseResult->term?->value ?? $courseResult->term,
                'for_mid_term' => $courseResult->for_mid_term
              ]
            )
            ->values()
            ->all()
        ];
      })
      ->values()
      ->all();
  }

  private function buildPrompt(array $payload): string
  {
    $json = json_encode($payload, JSON_PRETTY_PRINT);

    return "Analyze this JSON array of student term result records and their subject-score history. Generate a suitable teacher_comment and principal_comment for each term_result_id.
Each comment must be one sentence, or at most two sentences. Keep the tone professional, specific to the student's performance, and appropriate for a school result sheet. Avoid inventing subjects or scores not present in the data.
Some records may already have one existing comment. Preserve the intent of any existing comment and generate the missing comment so it complements the one already present.
Return only valid JSON. The JSON must be an array of objects with exactly these keys: term_result_id, teacher_comment, principal_comment.

Student result data:
{$json}";
  }

  private function applyComments(Collection $termResults, array $comments): int
  {
    $termResultsById = $termResults->keyBy('id');
    $updatedCount = 0;

    foreach ($comments as $comment) {
      if (!is_array($comment)) {
        continue;
      }

      $termResult = $termResultsById->get($comment['term_result_id'] ?? null);
      $teacherComment = trim($comment['teacher_comment'] ?? '');
      $principalComment = trim($comment['principal_comment'] ?? '');

      if (!$termResult) {
        continue;
      }

      $updates = [];
      if (blank($termResult->teacher_comment) && $teacherComment) {
        $updates['teacher_comment'] = $teacherComment;
      }
      if (blank($termResult->principal_comment) && $principalComment) {
        $updates['principal_comment'] = $principalComment;
      }

      if (empty($updates)) {
        continue;
      }

      $termResult->fill($updates)->save();
      $updatedCount++;
    }

    return $updatedCount;
  }
}
