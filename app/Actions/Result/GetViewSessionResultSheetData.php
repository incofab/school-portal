<?php

namespace App\Actions\Result;

use App\Enums\ResultCommentTemplateType;
use App\Enums\TermType;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\ResultCommentTemplate;
use App\Models\SessionResult;
use App\Models\TermResult;

class GetViewSessionResultSheetData
{
  public static function run(
    SessionResult $sessionResult,
    bool $onlyActivatedTermResults = false
  ): array {
    $sessionResult->loadMissing(
      'student.user',
      'academicSession',
      'classification.classificationGroup',
      'institution'
    );

    $binding = [
      'academic_session_id' => $sessionResult->academic_session_id,
      'classification_id' => $sessionResult->classification_id,
      'student_id' => $sessionResult->student_id,
      'for_mid_term' => false
    ];

    $termResultDetails = [];
    foreach (TermType::cases() as $term) {
      $termResult = TermResult::query()
        ->where($binding)
        ->where('term', $term->value)
        ->when(
          $onlyActivatedTermResults,
          fn($query) => $query->where('is_activated', true)
        )
        ->with('academicSession', 'classification', 'student.user')
        ->first();

      if (!$termResult) {
        continue;
      }

      $courseBinding = [...$binding, 'term' => $term->value];
      $termResultDetails[$term->value] = [
        'termResult' => $termResult,
        'courseResults' => CourseResult::query()
          ->select('course_results.*')
          ->join('courses', 'courses.id', 'course_results.course_id')
          ->where($courseBinding)
          ->with('course')
          ->orderBy('courses.order')
          ->orderBy('courses.title')
          ->get()
          ->keyBy('course_id'),
        'courseResultInfo' => CourseResultInfo::query()
          ->where(
            collect($courseBinding)
              ->except('student_id')
              ->toArray()
          )
          ->get()
          ->keyBy('course_id')
      ];
    }

    return [
      'sessionResult' => $sessionResult,
      'termResultDetails' => $termResultDetails,
      'resultCommentTemplate' => ResultCommentTemplate::getTemplate(
        $sessionResult->classification,
        type: ResultCommentTemplateType::SessionResult
      )
    ];
  }
}
