<?php
use App\Models\Event;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = User::factory()
    ->admin($this->institution)
    ->create();
  $this->nonAdminUser = User::factory()
    ->student($this->institution)
    ->create();
});

it('only allows admins to access the controller', function () {
  actingAs($this->admin)
    ->get(instRoute('events.index', [], $this->institution))
    ->assertStatus(200);
});

it('displays the create event form', function () {
  actingAs($this->admin)
    ->get(instRoute('events.create', [], $this->institution))
    ->assertStatus(200);
});

it('deletes an event', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->create();
  // Create an event (if needed)
  actingAs($this->admin)
    ->delete(
      instRoute(
        'events.destroy',
        [
          'event' => $event
        ],
        $this->institution
      )
    )
    ->assertStatus(200);
  assertDatabaseMissing('events', ['id' => $event->id]);
});

it('stores a new event', function () {
  $data = Event::factory()
    ->institution($this->institution)
    ->make()
    ->toArray();
  $data = collect($data)
    ->except('code')
    ->toArray();

  actingAs($this->admin)
    ->post(instRoute('events.store', [], $this->institution), $data)
    ->assertStatus(200);
  assertDatabaseHas('events', [...$data, 'duration' => $data['duration'] * 60]);
});

it('updates an event', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->create();

  $data = Event::factory()
    ->institution($this->institution)
    ->make()
    ->toArray();
  $data = collect($data)
    ->except('code')
    ->toArray();

  actingAs($this->admin)
    ->put(
      instRoute(
        'events.update',
        [
          'event' => $event
        ],
        $this->institution
      ),
      $data
    )
    ->assertStatus(200);
  assertDatabaseHas('events', [...$data, 'duration' => $data['duration'] * 60]);
});

it('downloads the event results as an excel file', function () {
  $event = Event::factory()
    ->institution($this->institution)
    ->create();
  Storage::fake();
  $student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $exam = Exam::factory()
    ->examable($student)
    ->examCourseables()
    ->create([
      'event_id' => $event->id,
      'examable_id' => $student->id,
      'examable_type' => $student->getMorphClass(),
      'score' => 80,
      'num_of_questions' => 100
    ]);

  // Act
  actingAs($this->admin)
    ->getJson(instRoute('events.download', [$event], $this->institution))
    ->assertOk()
    ->assertHeader(
      'Content-Type',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    )
    ->assertHeader(
      'Content-Disposition',
      'attachment; filename=' . sanitizeFilename("{$event->title}-exams.xlsx")
    );

  // Check if the file was created in the storage
  $fileName = sanitizeFilename("{$event->title}-exams.xlsx");
  $tempFilePath = storage_path("app/public/{$fileName}");
  expect(file_exists($tempFilePath))->toBeTrue();
  unlink($tempFilePath);
});
