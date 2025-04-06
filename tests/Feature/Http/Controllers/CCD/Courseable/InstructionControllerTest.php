<?php

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\EventCourseable;
use App\Models\Institution;
use App\Models\Instruction;

use function Pest\Laravel\actingAs;

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

test('index displays instructions for a course session', function ($class) {
  $courseable = $this->courseable[$class];
  $instructions = Instruction::factory(3)
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.instructions.index', [
      $this->institution,
      $courseable->getMorphedId()
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.course-sessions.instructions');
  $response->assertViewHas('allRecords');
  $response->assertViewHas('courseable');
  expect($response['allRecords']->count())->toBe(3);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new instruction', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Instruction::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->raw();

  $response = actingAs($this->instAdmin)->post(
    route('institutions.instructions.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    $data
  );

  $response->assertRedirect();
  expect(Instruction::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('edit displays a form to edit an instruction', function ($class) {
  $courseable = $this->courseable[$class];
  $instruction = Instruction::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.instructions.index', [
      $this->institution,
      $courseable->getMorphedId(),
      $instruction
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.course-sessions.instructions');
  $response->assertViewHas('edit', $instruction);
  $response->assertViewHas('courseable');
})->with([[CourseSession::class], [EventCourseable::class]]);

test('updates an existing instruction', function ($class) {
  $courseable = $this->courseable[$class];
  $instruction = Instruction::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $newData = Instruction::factory()->raw([
    'instruction' => 'Updated Instruction Text'
  ]);

  $response = actingAs($this->instAdmin)->put(
    route('institutions.instructions.update', [
      $this->institution,
      $instruction
    ]),
    $newData
  );

  $response->assertRedirect();
  expect(Instruction::first()->instruction)->toBe('Updated Instruction Text');
})->with([[CourseSession::class], [EventCourseable::class]]);

test('destroy deletes an instruction', function ($class) {
  $courseable = $this->courseable[$class];
  $instruction = Instruction::factory()
    ->for($this->institution)
    ->for($courseable, 'courseable')
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.instructions.destroy', [
      $this->institution,
      $instruction
    ])
  );

  $response->assertRedirect();
  expect(Instruction::count())->toBe(0);
})->with([[CourseSession::class], [EventCourseable::class]]);
