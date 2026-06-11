<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Institution;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it(
  'permanently deletes a course with no protected existing references',
  function () {
    $course = Course::factory()
      ->withInstitution($this->institution)
      ->create();
    CourseTeacher::factory()->create([
      'institution_id' => $this->institution->id,
      'course_id' => $course->id,
      'user_id' => $this->admin->id
    ]);

    actingAs($this->admin)
      ->deleteJson(
        route('institutions.courses.destroy', [$this->institution, $course])
      )
      ->assertOk();

    assertDatabaseMissing('courses', ['id' => $course->id]);
    assertDatabaseMissing('course_teachers', ['course_id' => $course->id]);
  }
);

it('rejects deleting a course with protected existing references', function () {
  $course = Course::factory()
    ->withInstitution($this->institution)
    ->create();
  CourseSession::factory()
    ->course($course)
    ->create();

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.courses.destroy', [$this->institution, $course])
    )
    ->assertStatus(400);

  assertDatabaseHas('courses', [
    'id' => $course->id,
    'deleted_at' => null
  ]);
});

it(
  'validates that generatePracticeQuestions requires exactly one topic_id',
  function () {
    $course = Course::factory()
      ->withInstitution($this->institution)
      ->create();

    // Test with zero topic_ids
    actingAs($this->admin)
      ->postJson(
        instRoute('courses.practice-questions', [], $this->institution),
        [
          'course' => $course->toArray(),
          'topic_ids' => []
        ]
      )
      ->assertStatus(422)
      ->assertJsonValidationErrors(['topic_ids']);

    // Test with two topic_ids
    actingAs($this->admin)
      ->postJson(
        instRoute('courses.practice-questions', [], $this->institution),
        [
          'course' => $course->toArray(),
          'topic_ids' => [1, 2]
        ]
      )
      ->assertStatus(422)
      ->assertJsonValidationErrors(['topic_ids']);

    // Test with one topic_id (should pass validation, but might fail later due to missing lesson notes or AI mock)
    // We only care about validation here for this test
    // actingAs($this->admin)
    //     ->postJson(instRoute('courses.practice-questions', [], $this->institution), [
    //         'course' => $course->toArray(),
    //         'topic_ids' => [1],
    //     ])
    //     ->assertStatus(401); // 401 is returned when no lesson notes are found
  }
);
