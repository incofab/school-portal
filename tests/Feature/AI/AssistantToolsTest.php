<?php

use App\Models\AiConversation;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\TermResult;
use App\Models\User;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantRunBudget;
use App\Services\AI\AssistantToolPlanner;
use App\Services\AI\AssistantToolRegistry;
use App\Services\AI\LaravelAiTextAgent;
use Laravel\Ai\Tools\Request as AiToolRequest;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  actingAs($this->admin);
  $this->context = app(AssistantContextResolver::class)->resolve(
    $this->institution
  );
});

it('registers typed read-only tools with schemas', function () {
  $definitions = app(AssistantToolRegistry::class)->definitions();

  expect(
    collect($definitions)
      ->pluck('name')
      ->all()
  )->toContain(
    'view_institution_profile',
    'list_academic_sessions',
    'list_authorized_classes',
    'list_authorized_students',
    'view_published_student_result',
    'view_student_attendance_summary',
    'view_class_attendance_summary',
    'view_student_fee_summary',
    'view_class_performance_summary',
    'list_authorized_teacher_assignments'
  );
  expect(
    collect($definitions)->every(
      fn(array $definition) => isset($definition['input_schema'])
    )
  )->toBeTrue();
});

it(
  'builds Laravel AI tools that execute only authorized registry capabilities',
  function () {
    $conversation = new AiConversation();
    $observations = [];
    $tools = app(AssistantToolPlanner::class)->laravelAiTools(
      $conversation,
      $this->context,
      new AssistantRunBudget(),
      $observations
    );
    $profileTool = collect($tools)->first(
      fn($tool) => $tool->name() === 'view_institution_profile'
    );

    $encodedResult = $profileTool->handle(new AiToolRequest());

    expect($encodedResult)
      ->toBeString()
      ->and(json_decode($encodedResult, true, flags: JSON_THROW_ON_ERROR)['ok'])
      ->toBeTrue()
      ->and($observations)
      ->toHaveCount(1)
      ->and($observations[0]['name'])
      ->toBe('view_institution_profile')
      ->and($observations[0]['arguments'])
      ->toBe([]);

    $resultTool = collect($tools)->first(
      fn($tool) => $tool->name() === 'view_published_student_result'
    );
    $resultTool->handle(
      new AiToolRequest([
        'student' => 'unknown',
        'academic_session' => 'unknown',
        'term' => 'first'
      ])
    );

    expect($observations)
      ->toHaveCount(2)
      ->and($observations[1]['arguments'])
      ->toBe([
        'student' => 'unknown',
        'academic_session' => 'unknown',
        'term' => 'first'
      ]);
  }
);

it('denies private tools to guests', function () {
  auth()->logout();
  $context = app(AssistantContextResolver::class)->resolve();
  $result = app(AssistantToolRegistry::class)->execute(
    'list_authorized_students',
    [],
    $context
  );

  expect($result->ok)
    ->toBeFalse()
    ->and($result->meta['reason'])
    ->toBe('unauthorized')
    ->and($result->data)
    ->toBe([]);

  expect(
    app(AssistantToolPlanner::class)->laravelAiTools(
      new AiConversation(),
      $context,
      new AssistantRunBudget()
    )
  )->toBe([]);
});

it(
  'returns a scoped student attendance summary for an explicit period',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution)
      ->create();
    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->create([
        'signed_in_at' => '2026-09-01 08:00:00',
        'signed_out_at' => '2026-09-01 15:00:00'
      ]);
    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->create([
        'signed_in_at' => '2026-09-02 08:00:00',
        'signed_out_at' => null
      ]);

    $result = app(AssistantToolRegistry::class)->execute(
      'view_student_attendance_summary',
      [
        'student' => $student->code,
        'from_date' => '2026-09-01',
        'to_date' => '2026-09-02'
      ],
      $this->context
    );

    expect($result->ok)
      ->toBeTrue()
      ->and($result->data['attendance_days'])
      ->toBe(2)
      ->and($result->data['signed_out_days'])
      ->toBe(1)
      ->and($result->data['expected_days'])
      ->toBeNull();
  }
);

it(
  'does not guess an attendance period or cross the institution boundary',
  function () {
    $student = Student::factory()
      ->withInstitution($this->institution)
      ->create();
    $otherInstitution = Institution::factory()->create();
    $otherStudent = Student::factory()
      ->withInstitution($otherInstitution)
      ->create();
    Attendance::factory()
      ->institutionUser($otherStudent->institutionUser)
      ->create(['signed_in_at' => '2026-09-01 08:00:00']);

    $registry = app(AssistantToolRegistry::class);
    $missingPeriod = $registry->execute(
      'view_student_attendance_summary',
      ['student' => $student->code],
      $this->context
    );
    $otherStudentResult = $registry->execute(
      'view_student_attendance_summary',
      [
        'student' => $otherStudent->code,
        'from_date' => '2026-09-01',
        'to_date' => '2026-09-02'
      ],
      $this->context
    );

    expect($missingPeriod->ok)
      ->toBeFalse()
      ->and($missingPeriod->meta['reason'])
      ->toBe('invalid_arguments')
      ->and($otherStudentResult->ok)
      ->toBeFalse()
      ->and($otherStudentResult->meta['reason'])
      ->toBe('invalid_arguments');
  }
);

it('returns class attendance rows only for an authorized class', function () {
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  Attendance::factory()
    ->institutionUser($student->institutionUser)
    ->create(['signed_in_at' => '2026-09-01 08:00:00']);

  $result = app(AssistantToolRegistry::class)->execute(
    'view_class_attendance_summary',
    [
      'classification_id' => $class->id,
      'from_date' => '2026-09-01',
      'to_date' => '2026-09-01'
    ],
    $this->context
  );

  expect($result->ok)
    ->toBeTrue()
    ->and($result->data['student_count'])
    ->toBe(1)
    ->and($result->data['students'][0]['attendance_days'])
    ->toBe(1);
});

it('returns only the authorized student fee balance', function () {
  $session = AcademicSession::factory()->create(['title' => '2025/2026']);
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  $fee = Fee::factory()
    ->institution($this->institution)
    ->create([
      'title' => 'Tuition',
      'amount' => 100000,
      'academic_session_id' => $session->id,
      'term' => 'first'
    ]);
  FeeCategory::factory()
    ->fee($fee)
    ->feeable($class)
    ->create();
  Receipt::factory()
    ->fee($fee)
    ->student($student)
    ->create([
      'amount' => 100000,
      'amount_paid' => 40000,
      'amount_remaining' => 60000,
      'term' => 'first',
      'academic_session_id' => $session->id
    ]);

  $result = app(AssistantToolRegistry::class)->execute(
    'view_student_fee_summary',
    [
      'student' => $student->code,
      'academic_session' => $session->title,
      'term' => 'first'
    ],
    $this->context
  );

  expect($result->ok)
    ->toBeTrue()
    ->and($result->data['total_amount_due'])
    ->toBe(100000.0)
    ->and($result->data['total_amount_paid'])
    ->toBe(40000.0)
    ->and($result->data['total_amount_remaining'])
    ->toBe(60000.0)
    ->and($result->data['fees'][0]['title'])
    ->toBe('Tuition');
});

it(
  'does not expose fee summaries to a teacher without fee permission',
  function () {
    $teacher = User::factory()->create();
    InstitutionUser::factory()
      ->withInstitution($this->institution)
      ->teacher()
      ->user($teacher)
      ->create();
    actingAs($teacher);
    $teacherContext = app(AssistantContextResolver::class)->resolve(
      $this->institution
    );

    $result = app(AssistantToolRegistry::class)->execute(
      'view_student_fee_summary',
      ['student' => 'me'],
      $teacherContext
    );

    expect($result->ok)
      ->toBeFalse()
      ->and($result->meta['reason'])
      ->toBe('unauthorized');
  }
);

it('returns only published and authorized class performance', function () {
  $session = AcademicSession::factory()->create(['title' => '2025/2026']);
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create(['title' => 'JSS 2 Blue']);
  $student = Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  TermResult::factory()
    ->forStudent($student)
    ->published()
    ->create([
      'academic_session_id' => $session->id,
      'classification_id' => $class->id,
      'term' => 'first',
      'average' => 82,
      'position' => 1,
      'for_mid_term' => false
    ]);

  $result = app(AssistantToolRegistry::class)->execute(
    'view_class_performance_summary',
    [
      'classification_id' => $class->id,
      'academic_session' => $session->title,
      'term' => 'first'
    ],
    $this->context
  );

  expect($result->ok)
    ->toBeTrue()
    ->and($result->data['class_average'])
    ->toBe(82.0)
    ->and($result->data['students_with_results'])
    ->toBe(1)
    ->and($result->data['students'][0]['name'])
    ->toBe($student->user->full_name);
});

it('treats mid-term results as published class performance', function () {
  $session = AcademicSession::factory()->create(['title' => '2025/2026']);
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  TermResult::factory()
    ->forStudent($student)
    ->create([
      'academic_session_id' => $session->id,
      'classification_id' => $class->id,
      'term' => 'first',
      'average' => 74,
      'position' => 1,
      'for_mid_term' => true,
      'result_publication_id' => null
    ]);

  $result = app(AssistantToolRegistry::class)->execute(
    'view_class_performance_summary',
    [
      'classification_id' => $class->id,
      'academic_session' => $session->title,
      'term' => 'first',
      'for_mid_term' => true
    ],
    $this->context
  );

  expect($result->ok)
    ->toBeTrue()
    ->and($result->data['students_with_results'])
    ->toBe(1)
    ->and($result->data['class_average'])
    ->toBe(74.0);
});

it('limits teacher assignment summaries to the current teacher', function () {
  $teacher = User::factory()->create();
  $otherTeacher = User::factory()->create();
  InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->user($teacher)
    ->create();
  InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->user($otherTeacher)
    ->create();
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $otherClass = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $otherCourse = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'user_id' => $teacher->id,
      'classification_id' => $class->id,
      'course_id' => $course->id
    ]);
  CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'user_id' => $otherTeacher->id,
      'classification_id' => $otherClass->id,
      'course_id' => $otherCourse->id
    ]);
  actingAs($teacher);
  $teacherContext = app(AssistantContextResolver::class)->resolve(
    $this->institution
  );

  $result = app(AssistantToolRegistry::class)->execute(
    'list_authorized_teacher_assignments',
    [],
    $teacherContext
  );

  expect($result->ok)
    ->toBeTrue()
    ->and($result->data['assignments'])
    ->toHaveCount(1)
    ->and($result->data['assignments'][0]['teacher']['id'])
    ->toBe($teacher->id);
});

it('scopes class and student tools to the current institution', function () {
  $otherInstitution = Institution::factory()->create();
  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create([
      'title' => 'JSS 2 Blue'
    ]);
  $otherClass = Classification::factory()
    ->withInstitution($otherInstitution)
    ->create([
      'title' => 'Other School Class'
    ]);
  $student = Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  Student::factory()
    ->withInstitution($otherInstitution, $otherClass)
    ->create();

  $registry = app(AssistantToolRegistry::class);
  $classes = $registry->execute('list_authorized_classes', [], $this->context);
  $students = $registry->execute(
    'list_authorized_students',
    [],
    $this->context
  );

  expect($classes->data['classes'])
    ->toHaveCount(1)
    ->and($classes->data['classes'][0]['title'])
    ->toBe('JSS 2 Blue')
    ->and($students->data['students'])
    ->toHaveCount(1)
    ->and($students->data['students'][0]['id'])
    ->toBe($student->id);
});

it('limits teacher class and student tools to assigned classes', function () {
  $teacher = User::factory()->create();
  $teacherInstitutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->user($teacher)
    ->create();
  actingAs($teacher);
  $assigned = Classification::factory()
    ->withInstitution($this->institution)
    ->create([
      'title' => 'Assigned Class',
      'form_teacher_id' => $teacher->id
    ]);
  $unassigned = Classification::factory()
    ->withInstitution($this->institution)
    ->create([
      'title' => 'Unassigned Class'
    ]);
  $assignedStudent = Student::factory()
    ->withInstitution($this->institution, $assigned)
    ->create();
  Student::factory()
    ->withInstitution($this->institution, $unassigned)
    ->create();
  $context = app(AssistantContextResolver::class)->resolve($this->institution);

  expect($context->institutionUser?->id)->toBe($teacherInstitutionUser->id);

  $classes = app(AssistantToolRegistry::class)->execute(
    'list_authorized_classes',
    [],
    $context
  );
  $students = app(AssistantToolRegistry::class)->execute(
    'list_authorized_students',
    [],
    $context
  );

  expect(
    collect($classes->data['classes'])
      ->pluck('title')
      ->all()
  )
    ->toBe(['Assigned Class'])
    ->and(
      collect($students->data['students'])
        ->pluck('id')
        ->all()
    )
    ->toBe([$assignedStudent->id]);
});

it('returns only published results within the actor scope', function () {
  $session = AcademicSession::factory()->create(['title' => '2025/2026']);
  $student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $result = TermResult::factory()
    ->forStudent($student)
    ->published()
    ->create([
      'academic_session_id' => $session->id,
      'term' => 'first',
      'average' => 78
    ]);
  $otherStudent = Student::factory()
    ->withInstitution(Institution::factory()->create())
    ->create();

  $registry = app(AssistantToolRegistry::class);
  $visible = $registry->execute(
    'view_published_student_result',
    [
      'student' => $student->code,
      'academic_session' => $session->title,
      'term' => 'first'
    ],
    $this->context
  );
  $hidden = $registry->execute(
    'view_published_student_result',
    [
      'student' => $otherStudent->code,
      'academic_session' => $session->title,
      'term' => 'first'
    ],
    $this->context
  );

  expect($visible->ok)
    ->toBeTrue()
    ->and($visible->data['results'][0]['id'])
    ->toBe($result->id)
    ->and($hidden->data)
    ->toBe([]);
});

it('exposes authorized read-only tools to the model request', function () {
  $fake = LaravelAiTextAgent::fake([
    'I found the authorized class list.'
  ]);

  $class = Classification::factory()
    ->withInstitution($this->institution)
    ->create([
      'title' => 'JSS 2 Blue'
    ]);
  Student::factory()
    ->withInstitution($this->institution, $class)
    ->create();
  $create = actingAs($this->admin)->postJson(
    route('institutions.assistant.conversations.store', $this->institution)
  );
  $conversationId = $create->json('conversation.id');

  $response = actingAs($this->admin)->postJson(
    route('institutions.assistant.messages.store', [
      $this->institution,
      $conversationId
    ]),
    ['message' => 'List the classes in my school.']
  );

  $response
    ->assertOk()
    ->assertJsonPath(
      'conversation.messages.1.content',
      'I found the authorized class list.'
    );

  LaravelAiTextAgent::assertPrompted(function ($request): bool {
    $toolNames = collect($request->agent->tools())
      ->map(fn($tool) => $tool->name())
      ->all();

    expect($toolNames)->toContain(
      'view_institution_profile',
      'list_authorized_classes',
      'list_authorized_students',
      'view_published_student_result'
    );

    return true;
  });
});
