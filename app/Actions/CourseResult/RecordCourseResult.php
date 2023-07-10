<?php
namespace App\Actions\CourseResult;

use App\Models\CourseTeacher;
use App\Models\CourseResult;
use App\Models\Assessment;

class RecordCourseResult
{
  public function __construct(
    private $data,
    private CourseTeacher $courseTeacher,
    private bool $processCourseResultForClass = false
  ) {
  }

  public static function run(
    $data,
    CourseTeacher $courseTeacher,
    bool $processCourseResultForClass = false
  ) {
    return (new self(
      $data,
      $courseTeacher,
      $processCourseResultForClass
    ))->execute();
  }

  public function execute()
  {
    $this->data['course_id'] = $this->courseTeacher->course_id;
    $this->data['teacher_user_id'] = $this->courseTeacher->user_id;
    $this->data['classification_id'] = $this->courseTeacher->classification_id;

    $bindingData = collect($this->data)
      ->only([
        'course_id',
        'student_id',
        'classification_id',
        'academic_session_id',
        'term',
        'for_mid_term'
      ])
      ->toArray();
    $courseResult = CourseResult::query()
      ->where($bindingData)
      ->first();

    [$result, $assessmentValues] = $this->getResultScore(
      $courseResult?->assessment_values ?? []
    );
    CourseResult::query()->updateOrCreate($bindingData, [
      ...collect($this->data)
        ->except('ass')
        ->toArray(),
      'result' => $result,
      'assessment_values' => $assessmentValues,
      'grade' => GetGrade::run($result)
    ]);

    if ($this->processCourseResultForClass) {
      EvaluateCourseResultForClass::run(
        $this->courseTeacher->classification,
        $this->courseTeacher->course_id,
        $this->data['academic_session_id'],
        $this->data['term'],
        $this->data['for_mid_term']
      );
    }
  }

  private function getResultScore(array $existingAssessmentValues)
  {
    $term = $this->data['term'];
    $forMidTerm = $this->data['for_mid_term'] ?? false;

    $assessments = Assessment::query()
      ->forMidTerm($forMidTerm)
      ->forTerm($term)
      ->get();

    $result = $this->data['exam'];

    $allAssessmentValues = [
      ...$existingAssessmentValues,
      ...$this->data['ass']
    ];
    $assessmentValues = [];
    foreach ($assessments as $key => $assessment) {
      $title = $assessment->title;
      $assessmentScore = $allAssessmentValues[$title] ?? 0;
      $result += $assessmentScore;
      $assessmentValues[$title] = $assessmentScore;
    }
    return [$result, $assessmentValues];
  }
}
