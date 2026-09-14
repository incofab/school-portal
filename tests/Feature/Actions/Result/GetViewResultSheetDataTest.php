<?php

use App\Actions\Result\GetViewResultSheetData;
use App\Models\Assessment;
use App\Models\CourseResult;
use Illuminate\Database\Eloquent\Collection;

function filterResultSheetAssessments(
  Collection $assessments,
  Collection $courseResults
): array {
  $method = new \ReflectionMethod(
    GetViewResultSheetData::class,
    'filterAssessments'
  );
  $method->setAccessible(true);

  return $method->invoke(null, $assessments, $courseResults);
}

function resultSheetAssessment(int $id, string $title): Assessment
{
  $assessment = new Assessment();
  $assessment->setRawAttributes(['id' => $id, 'title' => $title], true);

  return $assessment;
}

function resultSheetCourseResult(array $assessmentValues): CourseResult
{
  $courseResult = new CourseResult();
  $courseResult->setRawAttributes(
    ['assessment_values' => json_encode($assessmentValues)],
    true
  );

  return $courseResult;
}

it('returns assessments recorded by any displayed course result', function () {
  $firstAssessment = resultSheetAssessment(1, 'first_assessment');
  $secondAssessment = resultSheetAssessment(2, 'second_assessment');
  $unusedAssessment = resultSheetAssessment(3, 'unused_assessment');

  $result = filterResultSheetAssessments(
    new Collection([$firstAssessment, $secondAssessment, $unusedAssessment]),
    new Collection([
      resultSheetCourseResult([
        $firstAssessment->assessmentResultKey() => 0
      ]),
      resultSheetCourseResult([
        $secondAssessment->assessmentResultKey() => 12
      ])
    ])
  );

  expect($result)->toEqual([$firstAssessment, $secondAssessment]);
});

it('recognizes legacy and renamed assessment result keys', function () {
  $legacyAssessment = resultSheetAssessment(1, 'renamed_assessment');
  $renamedAssessment = resultSheetAssessment(2, 'current_title');

  $result = filterResultSheetAssessments(
    new Collection([$legacyAssessment, $renamedAssessment]),
    new Collection([
      resultSheetCourseResult([
        $legacyAssessment->raw_title => null,
        'previous_title|' . $renamedAssessment->id => 8
      ])
    ])
  );

  expect($result)->toEqual([$legacyAssessment, $renamedAssessment]);
});

it(
  'returns no assessments when displayed course results have no scores',
  function () {
    $assessment = resultSheetAssessment(1, 'first_assessment');

    $result = filterResultSheetAssessments(
      new Collection([$assessment]),
      new Collection([resultSheetCourseResult([])])
    );

    expect($result)->toBe([]);
  }
);
