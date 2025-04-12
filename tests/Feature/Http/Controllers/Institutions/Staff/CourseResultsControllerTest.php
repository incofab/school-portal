<?php

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->course = $this->courseTeacher->course;
  $this->classification = $this->courseTeacher->classification;
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First->value;
  [$this->assessment1, $this->assessment2] = $this->institution->assessments;

  $this->route = route('institutions.course-results.store', [
    $this->institution->uuid,
    $this->courseTeacher
  ]);
  $this->classResultRoute = route('institutions.record-class-results.store', [
    $this->institution->uuid,
    $this->courseTeacher
  ]);
  $this->requestData = [
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false
  ];
});

it('records course result for a student', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $result = [
    'student_id' => $student->id,
    'exam' => 40,
    'ass' => [
      $this->assessment1->raw_title => 15,
      $this->assessment2->raw_title => 14
    ]
  ];

  actingAs($this->instAdmin)
    ->postJson($this->route, [...$this->requestData, 'result' => [$result]])
    ->assertOk();

  $courseResult = CourseResult::where([
    ...$this->requestData,
    'institution_id' => $this->institution->id,
    'student_id' => $student->id,
    'course_id' => $this->courseTeacher->course_id,
    'classification_id' => $this->courseTeacher->classification_id,
    'teacher_user_id' => $this->courseTeacher->user_id
  ])->first();

  expect($courseResult)
    ->exam->toBe(floatval($result['exam']))
    ->result->toBe(floatval($result['exam'] + 15 + 14));
  expect(count($courseResult['assessment_values']))->toBe(2);
});

it('records course result for multiple students', function () {
  [$student1, $student2] = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->count(4)
    ->create();

  $result = [
    [
      'student_id' => $student1->id,
      'exam' => 40,
      'ass' => [
        $this->assessment1->raw_title => 15,
        $this->assessment2->raw_title => 15
      ]
    ],
    [
      'student_id' => $student2->id,
      'exam' => 30,
      'ass' => [
        $this->assessment1->raw_title => 10,
        $this->assessment2->raw_title => 10
      ]
    ]
  ];

  actingAs($this->instAdmin)
    ->postJson($this->classResultRoute, [
      ...$this->requestData,
      'result' => $result
    ])
    // ->dump()
    ->assertOk();

  $courseResultQueryData = [
    ...$this->requestData,
    'institution_id' => $this->institution->id,
    'course_id' => $this->courseTeacher->course_id,
    'classification_id' => $this->courseTeacher->classification_id,
    'teacher_user_id' => $this->courseTeacher->user_id
  ];

  $courseResult1 = CourseResult::where([
    ...$courseResultQueryData,
    'student_id' => $student1->id
  ])->first();
  $courseResult2 = CourseResult::where([
    ...$courseResultQueryData,
    'student_id' => $student2->id
  ])->first();

  expect($courseResult1)
    ->exam->toBe(floatval(40))
    ->result->toBe(floatval(40 + 15 + 15));
  expect(count($courseResult1['assessment_values']))->toBe(2);

  expect($courseResult2)
    ->exam->toBe(floatval(30))
    ->result->toBe(floatval(30 + 10 + 10));
  expect(count($courseResult2['assessment_values']))->toBe(2);
});

it('deletes an existing course result', function () {
  [$c1, $c2] = CourseResult::factory(2)
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->instAdmin)
    ->deleteJson(
      route('institutions.course-results.destroy', [
        $this->institution->uuid,
        $c1
      ])
    )
    ->assertOk();
  assertDatabaseMissing('course_results', ['id' => $c1->id]);
});
