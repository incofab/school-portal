<?php

use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\FullTermType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
  ClassResultInfo::clearResultLockCache();

  $this->institution = Institution::factory()->create();
  $this->institution->assessments()->delete();

  $this->academicSession = AcademicSession::factory()->create();
  $this->courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->classification = $this->courseTeacher->classification;
  $this->student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();

  $this->baseData = [
    'institution_id' => $this->institution->id,
    'student_id' => $this->student->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'for_mid_term' => false
  ];
});

function recordCourseResultTestAssessment(array $attributes): Assessment
{
  $obj = test();
  return Assessment::factory()->create([
    'institution_id' => $obj->institution->id,
    ...$attributes
  ]);
}

function recordCourseResultTestResult(): CourseResult
{
  $obj = test();
  return CourseResult::query()
    ->where([
      'institution_id' => $obj->institution->id,
      'student_id' => $obj->student->id,
      'academic_session_id' => $obj->academicSession->id,
      'course_id' => $obj->courseTeacher->course_id,
      'classification_id' => $obj->courseTeacher->classification_id,
      'teacher_user_id' => $obj->courseTeacher->user_id,
      'term' => $obj->baseData['term'],
      'for_mid_term' => $obj->baseData['for_mid_term']
    ])
    ->firstOrFail();
}

it(
  'creates a course result with course teacher binding data and total score',
  function () {
    $firstAssessment = recordCourseResultTestAssessment([
      'title' => 'first_assessment',
      'max' => 20
    ]);
    $secondAssessment = recordCourseResultTestAssessment([
      'title' => 'second_assessment',
      'max' => 20
    ]);

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 45,
        'ass' => [
          $firstAssessment->raw_title => 18,
          $secondAssessment->raw_title => 17
        ]
      ],
      $this->courseTeacher
    );

    $courseResult = recordCourseResultTestResult();

    expect($courseResult)
      ->course_id->toBe($this->courseTeacher->course_id)
      ->teacher_user_id->toBe($this->courseTeacher->user_id)
      ->classification_id->toBe($this->classification->id)
      ->exam->toBe(45.0)
      ->result->toBe(80.0)
      ->grade->toBe('A');
    expect($courseResult->assessment_values->toArray())->toBe([
      'first_assessment' => 18,
      'second_assessment' => 17
    ]);
  }
);

it(
  'updates an existing course result without creating duplicates',
  function () {
    $assessment = recordCourseResultTestAssessment([
      'title' => 'project',
      'max' => 20
    ]);

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 30,
        'ass' => [$assessment->raw_title => 10]
      ],
      $this->courseTeacher
    );
    $firstRecord = recordCourseResultTestResult();

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 50,
        'ass' => [$assessment->raw_title => 15]
      ],
      $this->courseTeacher
    );
    $updatedRecord = recordCourseResultTestResult();

    expect($updatedRecord->id)
      ->toBe($firstRecord->id)
      ->and($updatedRecord->exam)
      ->toBe(50.0)
      ->and($updatedRecord->result)
      ->toBe(65.0)
      ->and($updatedRecord->assessment_values->toArray())
      ->toBe([
        'project' => 15
      ]);
    expect(
      CourseResult::query()
        ->where('student_id', $this->student->id)
        ->where('course_id', $this->courseTeacher->course_id)
        ->where('classification_id', $this->classification->id)
        ->where('academic_session_id', $this->academicSession->id)
        ->where('term', TermType::First->value)
        ->where('for_mid_term', false)
        ->count()
    )->toBe(1);
  }
);

it(
  'preserves existing exam and assessment values when a partial update is recorded',
  function () {
    [$firstAssessment, $secondAssessment] = [
      recordCourseResultTestAssessment([
        'title' => 'first_assessment',
        'max' => 20
      ]),
      recordCourseResultTestAssessment([
        'title' => 'second_assessment',
        'max' => 20
      ])
    ];

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 40,
        'ass' => [
          $firstAssessment->raw_title => 12,
          $secondAssessment->raw_title => 13
        ]
      ],
      $this->courseTeacher
    );

    RecordCourseResult::run(
      [...$this->baseData, 'ass' => [$secondAssessment->raw_title => 18]],
      $this->courseTeacher
    );

    $courseResult = recordCourseResultTestResult();

    expect($courseResult)
      ->exam->toBe(40.0)
      ->result->toBe(70.0)
      ->grade->toBe('A');
    expect($courseResult->assessment_values->toArray())->toBe([
      'first_assessment' => 12,
      'second_assessment' => 18
    ]);
  }
);

it(
  'defaults missing configured assessments to zero and ignores unknown assessment keys',
  function () {
    recordCourseResultTestAssessment([
      'title' => 'configured_assessment',
      'max' => 20
    ]);

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 55,
        'ass' => [
          'unknown_assessment' => 20
        ]
      ],
      $this->courseTeacher
    );

    $courseResult = recordCourseResultTestResult();

    expect($courseResult)
      ->exam->toBe(55.0)
      ->result->toBe(55.0)
      ->grade->toBe('C');
    expect($courseResult->assessment_values->toArray())->toBe([
      'configured_assessment' => 0
    ]);
  }
);

it('records exam-only results when no assessments are configured', function () {
  RecordCourseResult::run(
    [...$this->baseData, 'exam' => 44],
    $this->courseTeacher
  );

  $courseResult = recordCourseResultTestResult();

  expect($courseResult)
    ->exam->toBe(44.0)
    ->result->toBe(44.0)
    ->grade->toBe('E');
  expect($courseResult->assessment_values->toArray())->toBe([]);
});

it(
  'only uses assessments that match the current term midterm flag and classification',
  function () {
    $matchingAssessment = recordCourseResultTestAssessment([
      'title' => 'matching_assessment',
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'max' => 20
    ]);
    $midtermAssessment = recordCourseResultTestAssessment([
      'title' => 'midterm_assessment',
      'term' => TermType::First->value,
      'for_mid_term' => true,
      'max' => 20
    ]);
    $otherTermAssessment = recordCourseResultTestAssessment([
      'title' => 'second_term_assessment',
      'term' => TermType::Second->value,
      'for_mid_term' => false,
      'max' => 20
    ]);
    $otherClassAssessment = recordCourseResultTestAssessment([
      'title' => 'other_class_assessment',
      'max' => 20
    ]);
    $otherClassAssessment
      ->classifications()
      ->attach(
        $this->institution
          ->classifications()
          ->create(['title' => 'Other Class'])->id
      );

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'exam' => 40,
        'ass' => [
          $matchingAssessment->raw_title => 10,
          $midtermAssessment->raw_title => 10,
          $otherTermAssessment->raw_title => 10,
          $otherClassAssessment->raw_title => 10
        ]
      ],
      $this->courseTeacher
    );

    $courseResult = recordCourseResultTestResult();

    expect($courseResult->result)->toBe(50.0);
    expect($courseResult->assessment_values->toArray())->toBe([
      'matching_assessment' => 10
    ]);
  }
);

it(
  'uses a dependent assessment score from the mapped previous result',
  function () {
    $dependentAssessment = recordCourseResultTestAssessment([
      'title' => 'first_term_average',
      'term' => TermType::Second->value,
      'for_mid_term' => false,
      'max' => 20,
      'depends_on' => FullTermType::First->value
    ]);

    CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $this->student->id,
      'teacher_user_id' => $this->courseTeacher->user_id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => TermType::First->value,
      'for_mid_term' => false,
      'exam' => 60,
      'result' => 80,
      'assessment_values' => []
    ]);

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'term' => TermType::Second->value,
        'exam' => 50,
        'ass' => [
          $dependentAssessment->raw_title => 5
        ]
      ],
      $this->courseTeacher
    );

    $courseResult = CourseResult::query()
      ->where('student_id', $this->student->id)
      ->where('course_id', $this->courseTeacher->course_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::Second->value)
      ->where('for_mid_term', false)
      ->firstOrFail();

    expect($courseResult->result)->toBe(66.0);
    expect($courseResult->assessment_values->toArray())->toBe([
      'first_term_average' => 16
    ]);
  }
);

it(
  'falls back to the submitted score when a dependent result does not exist',
  function () {
    $dependentAssessment = recordCourseResultTestAssessment([
      'title' => 'missing_dependency',
      'term' => TermType::Second->value,
      'for_mid_term' => false,
      'max' => 20,
      'depends_on' => FullTermType::FirstMid->value
    ]);

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'term' => TermType::Second->value,
        'exam' => 50,
        'ass' => [
          $dependentAssessment->raw_title => 7
        ]
      ],
      $this->courseTeacher
    );

    $courseResult = CourseResult::query()
      ->where('student_id', $this->student->id)
      ->where('course_id', $this->courseTeacher->course_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::Second->value)
      ->where('for_mid_term', false)
      ->firstOrFail();

    expect($courseResult->result)->toBe(57.0);
    expect($courseResult->assessment_values->toArray())->toBe([
      'missing_dependency' => 7
    ]);
  }
);

it(
  'creates course result analysis and positions when processing the class result',
  function () {
    recordCourseResultTestAssessment(['title' => 'assessment', 'max' => 20]);
    $secondStudent = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    RecordCourseResult::run(
      [...$this->baseData, 'exam' => 40, 'ass' => ['assessment' => 10]],
      $this->courseTeacher,
      true
    );

    RecordCourseResult::run(
      [
        ...$this->baseData,
        'student_id' => $secondStudent->id,
        'exam' => 70,
        'ass' => ['assessment' => 10]
      ],
      $this->courseTeacher,
      true
    );

    $courseResultInfo = CourseResultInfo::query()
      ->where('course_id', $this->courseTeacher->course_id)
      ->where('classification_id', $this->classification->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', TermType::First->value)
      ->where('for_mid_term', false)
      ->firstOrFail();

    expect($courseResultInfo)
      ->num_of_students->toBe(2)
      ->total_score->toBe(130.0)
      ->average->toBe(65.0)
      ->min_score->toBe(50.0)
      ->max_score->toBe(80.0);
    expect(recordCourseResultTestResult()->position)->toBe(2);
    expect(
      CourseResult::query()
        ->where('student_id', $secondStudent->id)
        ->where('course_id', $this->courseTeacher->course_id)
        ->where('classification_id', $this->classification->id)
        ->where('academic_session_id', $this->academicSession->id)
        ->where('term', TermType::First->value)
        ->where('for_mid_term', false)
        ->firstOrFail()->position
    )->toBe(1);
  }
);

it(
  'does not record or update results when the class result is locked',
  function () {
    recordCourseResultTestAssessment(['title' => 'assessment', 'max' => 20]);
    $existingCourseResult = CourseResult::query()->create([
      ...$this->baseData,
      'teacher_user_id' => $this->courseTeacher->user_id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'exam' => 40,
      'result' => 50,
      'assessment_values' => ['assessment' => 10],
      'grade' => 'C'
    ]);
    ClassResultInfo::factory()
      ->classification($this->classification)
      ->create([
        'academic_session_id' => $this->academicSession->id,
        'term' => TermType::First->value,
        'for_mid_term' => false,
        'is_locked' => true
      ]);
    ClassResultInfo::clearResultLockCache();

    expect(
      fn() => RecordCourseResult::run(
        [...$this->baseData, 'exam' => 40, 'ass' => ['assessment' => 10]],
        $this->courseTeacher
      )
    )->toThrow(HttpException::class);

    expect(
      CourseResult::query()
        ->where('student_id', $this->student->id)
        ->where('course_id', $this->courseTeacher->course_id)
        ->where('classification_id', $this->classification->id)
        ->where('academic_session_id', $this->academicSession->id)
        ->where('term', TermType::First->value)
        ->where('for_mid_term', false)
        ->count()
    )->toBe(1);
    expect($existingCourseResult->fresh())
      ->exam->toBe(40.0)
      ->result->toBe(50.0);
  }
);
