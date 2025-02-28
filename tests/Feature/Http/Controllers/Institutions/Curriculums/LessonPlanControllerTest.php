<?php

use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use App\Models\LessonPlan;
use App\Models\SchemeOfWork;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\Topic;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

/**
 * ./vendor/bin/pest --filter LessonPlanControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->admin = $this->institution->createdBy;
  $this->user = $this->institutionUser->user;

  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create(['course_id' => $this->course->id]);

  $this->classificationGroup = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->classification = Classification::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();

  $this->topic = Topic::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();

  $this->schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('tests the create page', function () {
  $route = route('institutions.lesson-plans.create', [
    'institution' => $this->institution->uuid,
    'schemeOfWork' => $this->schemeOfWork->id
  ]);

  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) {
      return $assert
        ->has('schemeOfWork')
        ->where('schemeOfWork.id', $this->schemeOfWork->id)
        ->component('institutions/lesson-plans/create-edit-lesson-plan');
    });
});

it('tests the edit page', function () {
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create();

  $route = route('institutions.lesson-plans.edit', [
    'institution' => $this->institution->uuid,
    'lessonPlan' => $lessonPlan->id
  ]);

  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->has('lessonPlan')
        ->where('lessonPlan.id', $lessonPlan->id)
        ->component('institutions/lesson-plans/create-edit-lesson-plan')
    );
});

it('stores lesson plan data', function () {
  $route = route('institutions.lesson-plans.store-or-update', [
    'institution' => $this->institution->uuid
  ]);

  // actingAs($this->admin)
  //     ->postJson($route, [])
  //     ->assertJsonValidationErrors(['scheme_of_work_id']);

  $lessonPlanData = [
    'course_teacher_id' => $this->courseTeacher->id,
    'objective' => 'Test Objective',
    'activities' => 'Test Activities',
    'content' => 'Test Content',
    'scheme_of_work_id' => $this->schemeOfWork->id
  ];

  actingAs($this->admin)
    ->postJson($route, [
      ...$lessonPlanData,
      'is_used_by_institution_group' => true
    ])
    ->assertOk();

  assertDatabaseCount('lesson_plans', 1);
  assertDatabaseHas('lesson_plans', $lessonPlanData);
});

it('updates lesson plan data', function () {
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create();

  $route = route('institutions.lesson-plans.store-or-update', [
    'institution' => $this->institution->uuid,
    'lessonPlan' => $lessonPlan->id
  ]);

  $dCourseTeacher = $this->courseTeacher;

  $updatedData = [
    'scheme_of_work_id' => $this->schemeOfWork->id,
    'course_teacher_id' => $dCourseTeacher->id,
    'objective' => 'Updated Test Objective',
    'activities' => 'Updated Test Activities',
    'content' => 'Updated Test Content',
    'is_used_by_institution_group' => false
  ];

  actingAs($dCourseTeacher->user)
    ->postJson($route, $updatedData)
    ->assertOk();

  $lessonPlan->refresh();
  assertEquals($updatedData['objective'], $lessonPlan->objective);
  assertEquals($updatedData['activities'], $lessonPlan->activities);
  assertEquals($updatedData['content'], $lessonPlan->content);
});

it('deletes a lesson plan', function () {
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create();

  $route = route('institutions.lesson-plans.destroy', [
    'institution' => $this->institution->uuid,
    'lessonPlan' => $lessonPlan->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertOk();

  assertSoftDeleted('lesson_plans', ['id' => $lessonPlan->id]);
  // $this->assertDatabaseMissing('lesson_plans', ['id' => $lessonPlan->id]);
});

it('restricts non-admin access to lesson plan routes', function () {
  $lessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create();
  $nonAdminUser = User::factory()
    ->student($this->institution)
    ->create();

  $routes = [
    'create' => route('institutions.lesson-plans.create', [
      'institution' => $this->institution->uuid,
      'schemeOfWork' => $this->schemeOfWork->id
    ]),
    'edit' => route('institutions.lesson-plans.edit', [
      'institution' => $this->institution->uuid,
      'lessonPlan' => $lessonPlan->id
    ]),
    'store' => route('institutions.lesson-plans.store-or-update', [
      'institution' => $this->institution->uuid
    ]),
    'update' => route('institutions.lesson-plans.store-or-update', [
      'institution' => $this->institution->uuid,
      'lessonPlan' => $lessonPlan->id
    ]),
    'destroy' => route('institutions.lesson-plans.destroy', [
      'institution' => $this->institution->uuid,
      'lessonPlan' => $lessonPlan->id
    ])
  ];

  foreach ($routes as $name => $route) {
    $method = match ($name) {
      'create', 'edit' => 'getJson',
      'store' => 'postJson',
      'update' => 'postJson',
      'destroy' => 'deleteJson'
    };

    actingAs($nonAdminUser)
      ->$method(
        $route,
        $name === 'store' || $name === 'update' ? ['title' => 'Test'] : []
      )
      ->assertForbidden();
  }
});
