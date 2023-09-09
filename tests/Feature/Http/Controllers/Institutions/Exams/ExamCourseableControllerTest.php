<?php

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->create();
  $this->exam = Exam::factory()
    ->event($this->event)
    ->create();
  $this->admin = User::factory()
    ->admin($this->institution)
    ->create();
});

it('only allows admins to access the controller', function () {
  $nonAdminUser = User::factory()
    ->student($this->institution)
    ->create();

  // Attempt to access the controller as a non-admin
  actingAs($nonAdminUser)
    ->getJson(
      instRoute('exam-courseables.index', [$this->exam], $this->institution)
    )
    ->assertStatus(403);

  // Attempt to access the controller as an admin
  actingAs($this->admin)
    ->getJson(
      instRoute('exam-courseables.index', [$this->exam], $this->institution)
    )
    ->assertStatus(200);
});

it('displays the create exam-courseable form', function () {
  actingAs($this->admin)
    ->getJson(
      instRoute('exam-courseables.create', [$this->exam], $this->institution)
    )
    ->assertStatus(200);
});

it('deletes an exam-courseable', function () {
  $examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->create();
  // Create an exam (if needed)
  actingAs($this->admin)
    ->deleteJson(
      instRoute(
        'exam-courseables.destroy',
        [$examCourseable],
        $this->institution
      )
    )
    ->assertStatus(200);
  assertDatabaseMissing('exam_courseables', ['id' => $examCourseable->id]);
});

it('stores new exam courseables', function () {
  $courseable = CourseSession::factory()
    ->institution($this->institution)
    ->create();
  $eventCourseables = EventCourseable::factory()
    ->count(3)
    ->event($this->event)
    ->create();
  actingAs($this->admin)
    ->postJson(
      instRoute('exam-courseables.store', [$this->exam], $this->institution),
      [
        'courseables' => $eventCourseables
          ->map(
            fn($item) => [
              'courseable_id' => $item->courseable->id,
              'courseable_type' => $item->courseable->getMorphClass()
            ]
          )
          ->toArray()
      ]
    )
    ->assertStatus(200);
  $eventCourseables->map(
    fn($item) => assertDatabaseHas('exam_courseables', [
      'exam_id' => $this->exam->id,
      'courseable_type' => $item->courseable->getMorphClass(),
      'courseable_id' => $item->courseable->id
    ])
  );
});
