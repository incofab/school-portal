<?php

use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\InstitutionUser;
use App\Models\SchemeOfWork;
use App\Models\Student;
use App\Models\Topic;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertEquals;

/**
 * ./vendor/bin/pest --filter LessonNoteControllerTest
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

  $this->classificationGroup = ClassificationGroup::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->classification = Classification::factory()
    ->classificationGroup($this->classificationGroup)
    ->create();

  $this->courseTeacher = CourseTeacher::factory()
    ->withInstitution($this->institution)
    ->create([
      'course_id' => $this->course->id,
      'classification_id' => $this->classification->id
    ]);

  $this->topic = Topic::factory()
    ->classificationGroup($this->classificationGroup)
    ->create(['course_id' => $this->course->id]);

  $this->schemeOfWork = SchemeOfWork::factory()
    ->topic($this->topic)
    ->create();

  $this->lessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create(['course_teacher_id' => $this->courseTeacher->id]);

  $this->lessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan, $this->classification)
    ->create([
      'course_id' => $this->course->id,
      'course_teacher_id' => $this->courseTeacher->id
    ]);

  $this->student = Student::factory()
    ->withInstitution($this->institution, $this->classification)
    ->create();
});

it('tests the index page', function () {
  $route = route('institutions.lesson-notes.index', [
    'institution' => $this->institution->uuid
  ]);

  //== Check if a Teacher can access the LessonNote List
  actingAs($this->courseTeacher->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) {
      return $assert
        ->has('lessonNotes')
        ->has('classificationGroups')
        ->component('institutions/lesson-notes/list-lesson-notes');
    });

  //== Check if a Student can access the LessonNote List
  actingAs($this->student->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) {
      return $assert
        ->has('lessonNotes')
        ->has('classificationGroups')
        ->component('institutions/lesson-notes/list-lesson-notes');
    });
});

it('tests the create page', function () {
  $newLessonPlan = LessonPlan::factory()
    ->schemeOfWork($this->schemeOfWork)
    ->create(['course_teacher_id' => $this->courseTeacher->id]);

  $route = route('institutions.lesson-notes.create', [
    'institution' => $this->institution->uuid,
    'lessonPlan' => $newLessonPlan->id
  ]);

  //== Ensure Students can't view the CREATE page of a LessonNote
  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();

  //== Ensure a CourseTeacher can view the CREATE page of a LessonNote
  actingAs($this->courseTeacher->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($newLessonPlan) {
      return $assert
        ->has('lessonPlan')
        ->where('lessonPlan.id', $newLessonPlan->id)
        ->component('institutions/lesson-notes/create-edit-lesson-note');
    });

  //== Ensure an Admin can view the CREATE page of a LessonNote
  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($newLessonPlan) {
      return $assert
        ->has('lessonPlan')
        ->where('lessonPlan.id', $newLessonPlan->id)
        ->component('institutions/lesson-notes/create-edit-lesson-note');
    });
});

it('tests the edit page', function () {
  $lessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan)
    ->courseTeacher($this->courseTeacher)
    ->create();

  $route = route('institutions.lesson-notes.edit', [
    'institution' => $this->institution->uuid,
    'lessonNote' => $lessonNote->id
  ]);

  //== Ensure a Student can NOT view the Edit page a LessonNote
  actingAs($this->student->user)
    ->getJson($route)
    ->assertForbidden();

  //== Ensure a CourseTeacher can view the Edit page a LessonNote
  actingAs($this->courseTeacher->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($lessonNote) {
      return $assert
        ->has('lessonNote')
        ->where('lessonNote.id', $lessonNote->id)
        ->component('institutions/lesson-notes/create-edit-lesson-note');
    });

  //== Ensure an Admin can view the Edit page a LessonNote
  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($lessonNote) {
      return $assert
        ->has('lessonNote')
        ->where('lessonNote.id', $lessonNote->id)
        ->component('institutions/lesson-notes/create-edit-lesson-note');
    });
});

it('stores lesson note data', function () {
  $route = route('institutions.lesson-notes.store-or-update', [
    'institution' => $this->institution->uuid
  ]);

  $dLessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan, $this->classification)
    ->make([
      'classification_group_id' => null,
      'institution_group_id' => $this->institution->institution_group_id,
      'course_id' => $this->course->id,
      'topic_id' => $this->topic->id,
      'course_teacher_id' => $this->courseTeacher->id
    ])
    ->toArray();

  $lessonNoteData = [
    ...$dLessonNote,
    'is_published' => true,
    'is_used_by_classification_group' => false,
    'is_used_by_institution_group' => true
  ];

  actingAs($this->admin)
    ->postJson($route, $lessonNoteData)
    ->assertOk();

  assertDatabaseCount('lesson_notes', 2); //== This should be 2 not 1, because the first record was added by $this->lessonNote
  assertDatabaseHas('lesson_notes', $dLessonNote);
});

it('updates lesson note data', function () {
  $lessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan)
    ->create();

  $route = route('institutions.lesson-notes.store-or-update', [
    'institution' => $this->institution->uuid,
    'lessonNote' => $lessonNote->id
  ]);

  $updatedData = [
    'title' => 'Updated Test Title',
    'content' => 'Updated Test Content',
    'is_published' => false,
    'is_used_by_classification_group' => true,
    'is_used_by_institution_group' => false
  ];

  //== Ensure that the courseTeacher can update a LessonNote
  actingAs($this->courseTeacher->user)
    ->postJson($route, $updatedData)
    ->assertOk();

  //== Ensure that an Admin can update a LessonNote
  actingAs($this->admin)
    ->postJson($route, $updatedData)
    ->assertOk();

  $lessonNote->refresh();
  assertEquals($updatedData['title'], $lessonNote->title);
  assertEquals($updatedData['content'], $lessonNote->content);
});

it('shows a lesson note', function () {
  $lessonNote = $this->lessonNote;

  $route = route('institutions.lesson-notes.show', [
    'institution' => $this->institution->uuid,
    'lessonNote' => $this->lessonNote->id
  ]);

  //== Ensure a Student from a different class can NOT access a particular LessonNote
  $anotherStudent = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($anotherStudent->user)
    ->getJson($route)
    ->assertForbidden();

  //== Ensure a Student can access a particular LessonNote
  actingAs($this->student->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($lessonNote) {
      return $assert
        ->has('lessonNote')
        ->where('lessonNote.id', $lessonNote->id)
        ->component('institutions/lesson-notes/show-lesson-note');
    });

  //== Ensure a courseTeacher can access a particular LessonNote
  actingAs($this->courseTeacher->user)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($lessonNote) {
      return $assert
        ->has('lessonNote')
        ->where('lessonNote.id', $lessonNote->id)
        ->component('institutions/lesson-notes/show-lesson-note');
    });

  //== Ensure an Admin can access a particular LessonNote
  actingAs($this->admin)
    ->getJson($route)
    ->assertOk()
    ->assertInertia(function (AssertableInertia $assert) use ($lessonNote) {
      return $assert
        ->has('lessonNote')
        ->where('lessonNote.id', $lessonNote->id)
        ->component('institutions/lesson-notes/show-lesson-note');
    });
});

it('deletes a lesson note', function () {
  $lessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan)
    ->create();

  $route = route('institutions.lesson-notes.destroy', [
    'institution' => $this->institution->uuid,
    'lessonNote' => $lessonNote->id
  ]);

  actingAs($this->admin)
    ->deleteJson($route)
    ->assertOk();

  assertSoftDeleted('lesson_notes', ['id' => $lessonNote->id]);
});

it('restricts students access to some lesson note routes', function () {
  $lessonNote = LessonNote::factory()
    ->lessonPlan($this->lessonPlan)
    ->create();

  $routes = [
    // 'index' => route('institutions.lesson-notes.index', ['institution' => $this->institution->uuid]),
    // 'show' => route('institutions.lesson-notes.show', ['institution' => $this->institution->uuid, 'lessonNote' => $lessonNote->id]),
    'create' => route('institutions.lesson-notes.create', [
      'institution' => $this->institution->uuid,
      'lessonPlan' => $this->lessonPlan->id
    ]),
    'edit' => route('institutions.lesson-notes.edit', [
      'institution' => $this->institution->uuid,
      'lessonNote' => $lessonNote->id
    ]),
    'store' => route('institutions.lesson-notes.store-or-update', [
      'institution' => $this->institution->uuid
    ]),
    'update' => route('institutions.lesson-notes.store-or-update', [
      'institution' => $this->institution->uuid,
      'lessonNote' => $lessonNote->id
    ]),
    'destroy' => route('institutions.lesson-notes.destroy', [
      'institution' => $this->institution->uuid,
      'lessonNote' => $lessonNote->id
    ])
  ];

  foreach ($routes as $name => $route) {
    $method = match ($name) {
      'create', 'edit' => 'getJson',
      'store', 'update' => 'postJson',
      'destroy' => 'deleteJson'
    };

    actingAs($this->student->user)
      ->$method(
        $route,
        $name === 'store' || $name === 'update' ? ['content' => 'Test'] : []
      )
      ->assertForbidden();
  }
});
