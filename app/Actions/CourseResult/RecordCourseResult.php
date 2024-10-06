<?php
namespace App\Actions\CourseResult;

use App\Actions\ResultUtil;
use App\Models\CourseTeacher;
use App\Models\CourseResult;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class RecordCourseResult
{
  private $bindingData;
  public function __construct(
    private $data,
    private CourseTeacher $courseTeacher,
    private bool $processCourseResultForClass = false
  ) {
    $this->data['course_id'] = $this->courseTeacher->course_id;
    $this->data['teacher_user_id'] = $this->courseTeacher->user_id;
    $this->data['classification_id'] = $this->courseTeacher->classification_id;

    $this->bindingData = collect($this->data)
      ->only([
        'course_id',
        'student_id',
        'classification_id',
        'academic_session_id',
        'term',
        'for_mid_term'
      ])
      ->toArray();
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
    $courseResult = CourseResult::query()
      ->where($this->bindingData)
      ->first();

    [$result, $assessmentValues] = $this->getResultScore(
      (array) ($courseResult?->assessment_values ?? [])
    );
    CourseResult::query()->updateOrCreate($this->bindingData, [
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

    $result = $this->data['exam'] ?? 0;

    $allAssessmentValues = [
      ...$existingAssessmentValues,
      ...$this->data['ass']
    ];
    $assessmentValues = [];

    /** @var Assessment $assessment */
    foreach ($assessments as $key => $assessment) {
      $title = $assessment->raw_title;
      $assessmentScore = $allAssessmentValues[$title] ?? 0;
      if ($assessment->depends_on) {
        $assessmentScore = $this->getDependentScore($assessment);
      }
      $result += $assessmentScore;
      $assessmentValues[$title] = $assessmentScore;
    }

    return [$result, $assessmentValues];
  }

  private function getDependentScore(Assessment $assessment)
  {
    [$term, $forMidTerm] = ResultUtil::fullTermMapping(
      $assessment->depends_on->value
    );
    $dependentCourseResult = CourseResult::query()
      ->where([
        ...$this->bindingData,
        'term' => $term,
        'for_mid_term' => $forMidTerm
      ])
      ->latest('id')
      ->first();

    $result = $dependentCourseResult?->result ?? 0;
    $score = round(($result / 100) * $assessment->max, 2);
    return $score;
  }
}
