<?php

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use App\Models\Classification;
use App\Models\InstitutionUser;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\ClassificationGroup;
use App\Models\Student;
use Carbon\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * ./vendor/bin/pest --filter AssignmentControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->user = User::factory()->create();
  $this->teacher = User::factory()->create();
  $this->student = User::factory()->create();

  $this->adminInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->admin->id,
    'role' => InstitutionUserType::Admin->value
  ]);

  $this->teacherInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->teacher->id,
    'role' => InstitutionUserType::Teacher->value
  ]);

  $this->studentInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $this->student->id,
    'role' => InstitutionUserType::Student->value
  ]);

  $this->course = Course::factory()->create([
    'institution_id' => $this->institution->id
  ]);

  $this->classifications = Classification::factory()
    ->count(2)
    ->create([
      'institution_id' => $this->institution->id
    ]);

  $this->classification = $this->classifications[0];

  // $this->classification = Classification::factory()->create([
  //   'institution_id' => $this->institution->id
  // ]);

  $this->courseTeacher = CourseTeacher::factory()->create([
    'institution_id' => $this->institution->id,
    'course_id' => $this->course->id,
    'classification_id' => $this->classification->id,
    'user_id' => $this->teacher->id
  ]);
});

it('admin can view assignments', function () {
  Assignment::factory(3)
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $route = route('institutions.assignments.index', $this->institution);

  actingAs($this->admin)
    ->get($route)
    ->assertOk();

  assertDatabaseCount('assignments', 3);
});

it('unauthenticated user cannot view assignments', function () {
  Assignment::factory(3)
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $route = route('institutions.assignments.index', $this->institution);

  actingAs($this->user)
    ->get($route)
    ->assertStatus(403);
});

it('teacher can view their assignments', function () {
  Assignment::factory(2)
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $route = route('institutions.assignments.index', $this->institution);

  actingAs($this->teacher)
    ->getJson($route)
    ->assertOk();

  assertDatabaseCount('assignments', 2);
});

it('teacher can view assignments of another teacher', function () {
  $classificationGroup = ClassificationGroup::factory()->create([
    'institution_id' => $this->institution->id
  ]);

  //= Another Teacher
  $anotherTeacher = User::factory()->create();
  $anotherTeacherInstitutionUser = InstitutionUser::factory()->create([
    'institution_id' => $this->institution->id,
    'user_id' => $anotherTeacher,
    'role' => InstitutionUserType::Teacher->value
  ]);

  Assignment::factory(3)
    ->withClassificationGroup(1, $classificationGroup)
    ->create([
      'course_id' => $this->course->id,
      'institution_id' => $this->institution->id,
      'institution_user_id' => $anotherTeacherInstitutionUser->id
    ]);

  $route = route('institutions.assignments.index', $this->institution);

  actingAs($this->teacher)
    ->getJson($route)
    ->assertOk();

  assertDatabaseCount('assignments', 3);
});

it('admin can create an assignment', function () {
  $classificationIds = $this->classifications->pluck('id');
  $academicSessionId = AcademicSession::factory()->create()->id;

  $assignmentData = [
    'institution_user_id' => $this->adminInstitutionUser->id,
    'course_id' => $this->course->id,
    'academic_session_id' => $academicSessionId,
    'term' => TermType::First->value,
    'status' => AssignmentStatus::Active->value,
    'max_score' => fake()->randomNumber(2) + 1,
    'content' => fake()->sentence(),
    'expires_at' => now()
      ->addDays(10)
      ->toDateTimeString()
  ];

  actingAs($this->admin)
    ->postJson(route('institutions.assignments.store', $this->institution), [
      ...$assignmentData,
      'classification_ids' => $classificationIds
    ])
    ->assertStatus(200);
  assertDatabaseHas('assignments', $assignmentData);
});

it('admin cannot create assignment with invalid data', function () {
  $classificationIds = $this->classifications->pluck('id');
  $academicSessionId = AcademicSession::factory()->create()->id;

  $assignmentData = [
    'institution_user_id' => $this->adminInstitutionUser->id,
    'course_id' => $this->course->id,
    'academic_session_id' => $academicSessionId,
    'term' => TermType::First->value,
    'status' => AssignmentStatus::Active->value,
    'max_score' => fake()->randomNumber(2) + 1,
    'content' => '', //= *Required - hence, Invalid submission.
    'expires_at' => now()->addDays(10)
  ];

  $response = actingAs($this->admin)->postJson(
    route('institutions.assignments.store', $this->institution),
    [...$assignmentData, 'classification_ids' => $classificationIds]
  );

  $response->assertStatus(422)->assertJsonValidationErrors(['content']);
});

it('admin can update an assignment', function () {
  $classificationIds = $this->classifications->pluck('id');
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'course_id' => $this->course->id,
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $updatedData = [
    'course_id' => $this->course->id,
    'content' => 'Updated Assignment Content',
    'max_score' => 30,
    'expires_at' => $assignment->expires_at->toDateTimeString(),
    'institution_user_id' => $this->adminInstitutionUser->id
  ];

  $response = actingAs($this->admin)->putJson(
    route('institutions.assignments.update', [$this->institution, $assignment]),
    [...$updatedData, 'classification_ids' => $classificationIds]
  );

  $response->assertStatus(200);
  assertDatabaseHas('assignments', $updatedData);
});

it('teacher can update their assignment', function () {
  $classificationIds = $this->classifications->pluck('id');
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'course_id' => $this->course->id,
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $updatedData = [
    'course_id' => $this->course->id,
    'content' => 'Updated Assignment Content',
    'max_score' => 30,
    'expires_at' => $assignment->expires_at->toDateTimeString(),
    'institution_user_id' => $this->teacherInstitutionUser->id
  ];

  $response = actingAs($this->teacher)->putJson(
    route('institutions.assignments.update', [$this->institution, $assignment]),
    [...$updatedData, 'classification_ids' => $classificationIds]
  );

  $response->assertStatus(200);
  assertDatabaseHas('assignments', $updatedData);
});

it('admin can delete an assignment', function () {
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $response = actingAs($this->admin)->deleteJson(
    route('institutions.assignments.destroy', [$this->institution, $assignment])
  );

  $response->assertStatus(200);
  assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});

it('teacher can delete their assignment', function () {
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $response = actingAs($this->teacher)->deleteJson(
    route('institutions.assignments.destroy', [$this->institution, $assignment])
  );

  $response->assertStatus(200);
  assertDatabaseMissing('assignments', ['id' => $assignment->id]);
});

it('show assignment', function () {
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  $response = actingAs($this->admin)->getJson(
    route('institutions.assignments.show', [$this->institution, $assignment])
  );

  $response->assertStatus(200);
});

it('student can view assignments', function () {
  Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  Student::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->studentInstitutionUser
    )
    ->create();

  $route = route('institutions.assignments.index', $this->institution);

  actingAs($this->student)
    ->getJson($route)
    ->assertOk();
});

it('student cannot view other classes assignments', function () {
  $anotherClassification = Classification::factory()
    ->count(2)
    ->create([
      'institution_id' => $this->institution->id
    ]);

  $assignment = Assignment::factory()
    ->withClassifications($anotherClassification)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id
    ]);

  Student::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->studentInstitutionUser
    )
    ->create();

  $route = route('institutions.assignments.show', [
    $this->institution,
    $assignment
  ]);

  $response = actingAs($this->student)->getJson($route);

  $response->assertStatus(403);
});

it('student cannot view assignments after deadline', function () {
  $assignment = Assignment::factory()
    ->withClassifications($this->classifications)
    ->create([
      'institution_id' => $this->institution->id,
      'institution_user_id' => $this->teacherInstitutionUser->id,
      'expires_at' => Carbon::now()->subDay()
    ]);

  Student::factory()
    ->withInstitution(
      $this->institution,
      $this->classification,
      $this->studentInstitutionUser
    )
    ->create();

  $response = actingAs($this->student)->getJson(
    route('institutions.assignments.show', [$this->institution, $assignment])
  );

  $response->assertStatus(403);
});
