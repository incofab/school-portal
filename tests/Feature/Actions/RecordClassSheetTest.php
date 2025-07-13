<?php

use App\Actions\CourseResult\RecordClassSheet;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First->value;
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  [$this->student1, $this->student2] = Student::factory(2)
    ->withInstitution($this->institution, $this->classification)
    ->create();
  // dd($this->student1->toArray());
  [$this->course1, $this->course2] = Course::factory(2)
    ->withInstitution($this->institution)
    ->create();
  $this->data = [
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'class_results' => [
      [
        'student_id' => $this->student1->id,
        'results' => [
          [
            'course_id' => $this->course1->id,
            'score' => 85.5
          ],
          [
            'course_id' => $this->course2->id,
            'score' => 74.2
          ]
        ]
      ],
      [
        'student_id' => $this->student2->id,
        'results' => [
          [
            'course_id' => $this->course1->id,
            'score' => 65.0
          ],
          [
            'course_id' => $this->course2->id,
            'score' => 65.0
          ]
        ]
      ]
    ]
  ];
});

it('confirms that the data is recorded correctly', function () {
  // Run the class sheet action
  $action = new RecordClassSheet(
    $this->institution,
    $this->data,
    $this->admin,
    $this->classification
  );
  $action->run();

  assertDatabaseHas('course_results', [
    'institution_id' => $this->institution->id,
    'academic_session_id' => $this->academicSession->id,
    'classification_id' => $this->classification->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'student_id' => $this->student1->id,
    'course_id' => $this->course1->id,
    'result' => 85.5
  ]);
  assertDatabaseHas('course_results', [
    'institution_id' => $this->institution->id,
    'academic_session_id' => $this->academicSession->id,
    'classification_id' => $this->classification->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'student_id' => $this->student1->id,
    'course_id' => $this->course2->id,
    'result' => 74.2
  ]);
  assertDatabaseCount('course_result_info', 2);
  assertDatabaseCount('term_results', 2);
});
