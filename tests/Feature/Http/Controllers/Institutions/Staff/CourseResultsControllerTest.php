<?php

use App\Enums\InstitutionSettingType;
use App\Enums\ResultExamMode;
use App\Enums\ResultSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\Student;
use App\Models\User;
use App\Support\SettingsHandler;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  ClassResultInfo::clearResultLockCache();
  SettingsHandler::clear();
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

it('lists course results by course order and then course title', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();

  $zoology = Course::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'Zoology', 'code' => 'ZOO', 'order' => 2]);
  $biology = Course::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'Biology', 'code' => 'BIO', 'order' => 1]);
  $algebra = Course::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'Algebra', 'code' => 'ALG', 'order' => 1]);

  foreach ([$zoology, $biology, $algebra] as $course) {
    CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $this->instAdmin->id,
      'course_id' => $course->id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => false
    ]);
  }

  actingAs($this->instAdmin)
    ->get(
      route('institutions.course-results.index', [
        $this->institution->uuid,
        'academicSession' => $this->academicSession->id,
        'term' => $this->term,
        'forMidTerm' => 0
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/courses/list-course-results')
        ->where('courseResults.data.0.course.title', 'Algebra')
        ->where('courseResults.data.1.course.title', 'Biology')
        ->where('courseResults.data.2.course.title', 'Zoology')
    );
});

it(
  'loads separate assessment groups and exam visibility for course result recording',
  function () {
    $this->institution->assessments()->delete();
    Assessment::factory()->create([
      'institution_id' => $this->institution->id,
      'title' => 'full_term_project',
      'for_mid_term' => false
    ]);
    Assessment::factory()->create([
      'institution_id' => $this->institution->id,
      'title' => 'mid_term_quiz',
      'for_mid_term' => true
    ]);
    Assessment::factory()->create([
      'institution_id' => $this->institution->id,
      'title' => 'second_term_project',
      'term' => TermType::Second->value,
      'for_mid_term' => false
    ]);
    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $this->institution->id,
        'key' => InstitutionSettingType::Result->value
      ],
      [
        'value' => json_encode([
          ResultSettingType::ExamMode->value => ResultExamMode::MidTerm->value
        ]),
        'type' => 'array'
      ]
    );

    actingAs($this->instAdmin)
      ->get(
        route('institutions.course-results.create', [
          $this->institution->uuid,
          $this->courseTeacher
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/courses/record-course-result')
          ->missing('assessments')
          ->where('assessmentGroups.fullTerm.0.raw_title', 'full_term_project')
          ->where('assessmentGroups.midTerm.0.raw_title', 'mid_term_quiz')
          ->missing('assessmentGroups.fullTerm.1')
          ->where('showExamInput.fullTerm', false)
          ->where('showExamInput.midTerm', true)
      );
  }
);

it(
  'loads the selected students existing course result on the recording page',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();
    $courseResult = CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => User::factory()->create()->id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => true,
      'exam' => 30,
      'result' => 45,
      'assessment_values' => [$this->assessment1->raw_title => 15]
    ]);

    actingAs($this->instAdmin)
      ->get(
        route('institutions.course-results.create', [
          $this->institution->uuid,
          $this->courseTeacher,
          'student_id' => $student->id,
          'academic_session_id' => $this->academicSession->id,
          'term' => $this->term,
          'for_mid_term' => 1
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/courses/record-course-result')
          ->where('courseResult.id', $courseResult->id)
          ->where('courseResult.student_id', $student->id)
          ->where('courseResult.for_mid_term', true)
          ->where('selectedStudent.id', $student->id)
          ->where('academic_session_id', $this->academicSession->id)
          ->where('term', $this->term)
          ->where('for_mid_term', true)
      );
  }
);

it(
  'keeps the selected student on the recording page when no result exists yet',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    actingAs($this->instAdmin)
      ->get(
        route('institutions.course-results.create', [
          $this->institution->uuid,
          $this->courseTeacher,
          'student_id' => $student->id,
          'academic_session_id' => $this->academicSession->id,
          'term' => $this->term,
          'for_mid_term' => 0
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/courses/record-course-result')
          ->where('courseResult', null)
          ->where('selectedStudent.id', $student->id)
          ->where('for_mid_term', false)
      );
  }
);

it(
  'loads both mid-term and full-term existing rows for class result recording',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();

    CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $this->courseTeacher->user_id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => false,
      'exam' => 60,
      'result' => 60
    ]);
    CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $this->courseTeacher->user_id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => true,
      'exam' => 30,
      'result' => 30
    ]);

    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $this->institution->id,
        'key' => InstitutionSettingType::CurrentAcademicSession->value
      ],
      ['value' => $this->academicSession->id]
    );
    InstitutionSetting::query()->updateOrCreate(
      [
        'institution_id' => $this->institution->id,
        'key' => InstitutionSettingType::CurrentTerm->value
      ],
      ['value' => $this->term]
    );

    actingAs($this->instAdmin)
      ->get(
        route('institutions.record-class-results.create', [
          $this->institution->uuid,
          $this->courseTeacher
        ])
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/courses/record-class-course-result')
          ->has('students.0.course_results', 2)
          ->where('students.0.course_results.0.for_mid_term', false)
          ->where('students.0.course_results.1.for_mid_term', true)
      );
  }
);

it('loads student subjects for student-wide result recording', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $this->course->update(['title' => 'A Subject']);
  $otherCourseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $this->classification->id
    ]);
  $otherCourseTeacher->course->update(['title' => 'Z Subject']);
  CourseResult::factory()->create([
    'institution_id' => $this->institution->id,
    'student_id' => $student->id,
    'teacher_user_id' => $this->courseTeacher->user_id,
    'course_id' => $this->courseTeacher->course_id,
    'classification_id' => $this->classification->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term,
    'for_mid_term' => false,
    'exam' => 45,
    'result' => 75,
    'assessment_values' => [$this->assessment1->raw_title => 15]
  ]);

  actingAs($this->instAdmin)
    ->get(
      route('institutions.record-student-subject-results.create', [
        $this->institution->uuid,
        'student_id' => $student->id,
        'academic_session_id' => $this->academicSession->id,
        'term' => $this->term,
        'for_mid_term' => 0
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/courses/record-student-subject-results')
        ->where('selectedStudent.id', $student->id)
        ->has('courseTeachers', 2)
        ->where('courseTeachers.0.course_results.0.exam', 45)
        ->where(
          'assessmentGroups.fullTerm.0.raw_title',
          $this->assessment1->raw_title
        )
    );
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

it('records all selected subject results for a student', function () {
  $student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
  $otherCourseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $this->classification->id
    ]);

  actingAs($this->instAdmin)
    ->postJson(
      route('institutions.record-student-subject-results.store', [
        $this->institution->uuid
      ]),
      [
        ...$this->requestData,
        'student_id' => $student->id,
        'result' => [
          [
            'course_teacher_id' => $this->courseTeacher->id,
            'exam' => 40,
            'ass' => [
              $this->assessment1->raw_title => 15,
              $this->assessment2->raw_title => 15
            ]
          ],
          [
            'course_teacher_id' => $otherCourseTeacher->id,
            'exam' => 35,
            'ass' => [
              $this->assessment1->raw_title => 12,
              $this->assessment2->raw_title => 13
            ]
          ]
        ]
      ]
    )
    ->assertOk();

  expect(
    CourseResult::query()
      ->where('student_id', $student->id)
      ->where('academic_session_id', $this->academicSession->id)
      ->where('term', $this->term)
      ->where('for_mid_term', false)
      ->count()
  )->toBe(2);
  expect(
    CourseResult::query()
      ->where('student_id', $student->id)
      ->where('course_id', $this->courseTeacher->course_id)
      ->first()
  )->result->toBe(floatval(70));
});

it(
  'does not record a course result when the class result is locked',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();
    ClassResultInfo::factory()
      ->classification($this->classification)
      ->create([
        'academic_session_id' => $this->academicSession->id,
        'term' => $this->term,
        'for_mid_term' => false,
        'is_locked' => true
      ]);

    actingAs($this->instAdmin)
      ->postJson($this->route, [
        ...$this->requestData,
        'result' => [
          [
            'student_id' => $student->id,
            'exam' => 40,
            'ass' => [
              $this->assessment1->raw_title => 15,
              $this->assessment2->raw_title => 14
            ]
          ]
        ]
      ])
      ->assertStatus(423)
      ->assertJsonFragment([
        'message' =>
          'This class result is locked. Unlock it from Class Result Analysis before adding or editing results.'
      ]);

    expect(
      CourseResult::query()
        ->where('student_id', $student->id)
        ->where('course_id', $this->courseTeacher->course_id)
        ->where('classification_id', $this->classification->id)
        ->where('academic_session_id', $this->academicSession->id)
        ->where('term', $this->term)
        ->where('for_mid_term', false)
        ->exists()
    )->toBeFalse();
  }
);

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

it(
  'does not delete an existing course result when the class result is locked',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution, $this->classification)
      ->create();
    $courseResult = CourseResult::factory()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'teacher_user_id' => $this->courseTeacher->user_id,
      'course_id' => $this->courseTeacher->course_id,
      'classification_id' => $this->classification->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term,
      'for_mid_term' => false,
      'exam' => 60,
      'result' => 60
    ]);
    ClassResultInfo::factory()
      ->classification($this->classification)
      ->create([
        'academic_session_id' => $this->academicSession->id,
        'term' => $this->term,
        'for_mid_term' => false,
        'is_locked' => true
      ]);

    actingAs($this->instAdmin)
      ->deleteJson(
        route('institutions.course-results.destroy', [
          $this->institution->uuid,
          $courseResult
        ])
      )
      ->assertStatus(423);

    expect($courseResult->fresh())->not->toBeNull();
  }
);
