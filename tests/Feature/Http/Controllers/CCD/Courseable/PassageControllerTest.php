<?php

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\EventCourseable;
use App\Models\Institution;
use App\Models\Passage;

use function Pest\Laravel\actingAs;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->courseSession = CourseSession::factory()
    ->course($this->course)
    ->create();

  $this->eventCourseable = EventCourseable::factory()
    ->institution($this->institution)
    ->create();
  $this->courseable = [
    CourseSession::class => $this->courseSession,
    EventCourseable::class => $this->eventCourseable
  ];
});

test('index displays passages for a course session', function ($class) {
  $courseable = $this->courseable[$class];
  $passages = Passage::factory(3)
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.passages.index', [
      $this->institution,
      $courseable->getMorphedId()
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.course-sessions.passages');
  $response->assertViewHas('allRecords');
  $response->assertViewHas('courseable');
  expect($response['allRecords']->count())->toBe(3);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new passage', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Passage::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->raw();

  $response = actingAs($this->instAdmin)->post(
    route('institutions.passages.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    $data
  );

  $response->assertRedirect();
  expect(Passage::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('edit displays a form to edit an passage', function ($class) {
  $courseable = $this->courseable[$class];
  $passage = Passage::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.passages.index', [
      $this->institution,
      $courseable->getMorphedId(),
      $passage
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.course-sessions.passages');
  $response->assertViewHas('edit', $passage);
  $response->assertViewHas('courseable');
})->with([[CourseSession::class], [EventCourseable::class]]);

test('updates an existing passage', function ($class) {
  $courseable = $this->courseable[$class];
  $passage = Passage::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $newData = Passage::factory()->raw([
    'passage' => 'Updated Passage Text'
  ]);

  $response = actingAs($this->instAdmin)->put(
    route('institutions.passages.update', [$this->institution, $passage]),
    $newData
  );

  $response->assertRedirect();
  expect($passage->fresh()->passage)->toBe('Updated Passage Text');
})->with([[CourseSession::class], [EventCourseable::class]]);

test('destroy deletes an passage', function ($class) {
  $courseable = $this->courseable[$class];
  $passage = Passage::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.passages.destroy', [$this->institution, $passage])
  );

  $response->assertRedirect();
  expect(Passage::count())->toBe(0);
})->with([[CourseSession::class], [EventCourseable::class]]);
