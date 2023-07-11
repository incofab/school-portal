<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class InjectAssessmentScoreFromTermResultController extends Controller
{
  public function create(
    Request $request,
    Institution $institution,
    Assessment $assessment
  ) {
    return inertia(
      'institutions/assessments/insert-assessment-score-from-course-result',
      ['assessment' => $assessment]
    );
  }

  public function store(
    Request $request,
    Institution $institution,
    Assessment $assessment
  ) {
    $data = $request->validate([
      'institution_id' => ['required'],
      // 'course_teacher_id' => ['required'],

      'from.academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'from.classification_id' => [
        'required',
        Rule::exists('classifications', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'from.term' => ['required', new Enum(TermType::class)],
      'from.for_mid_term' => ['required', 'boolean'],

      'to.academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'to.classification_id' => ['required', 'same:from.classification_id'],
      'to.term' => ['required', new Enum(TermType::class)],
      'to.for_mid_term' => ['required', 'boolean']
    ]);

    $bindingFrom = [...$data['from'], 'institution_id' => $institution->id];
    $bindingTo = [...$data['to'], 'institution_id' => $institution->id];

    $courseResults = CourseResult::query()
      ->where($bindingFrom)
      ->get();

    foreach ($courseResults as $key => $courseResult) {
      $this->bindResult($courseResult, $bindingTo, $assessment);
    }
  }

  private function bindResult(
    CourseResult $courseResult,
    array $bindingTo,
    Assessment $assessment
  ) {
    $studentExistingCourseResult = CourseResult::query()
      ->where($bindingTo)
      ->where('student_id', $courseResult->student_id)
      ->first();

    $updateData = [
      ...$bindingTo,
      'student_id' => $courseResult->student_id,
      'course_id' => $courseResult->course_id,
      'exam' => $studentExistingCourseResult?->exam ?? 0,
      'ass' => $this->getScoreBaseOnAssessment($courseResult, $assessment)
    ];
    $courseTeacher = $courseResult->courseTeacher();
    if (!$courseTeacher) {
      info(
        'bindResult(): Course teacher not found for => ' .
          json_encode($bindingTo)
      );
      return;
    }
    RecordCourseResult::run($updateData, $courseTeacher);
  }

  private function getScoreBaseOnAssessment(
    CourseResult $courseResult,
    Assessment $assessment
  ) {
    $result = $courseResult->result;
    return [
      $assessment->raw_title => round(($result / 100) * $assessment->max, 2)
    ];
  }
}
