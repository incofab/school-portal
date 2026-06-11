<?php

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Topic;
use App\Models\TopicPracticeAttempt;
use App\Models\TopicPracticeSummary;
use Inertia\Testing\AssertableInertia as Assert;

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

it(
  'records a submitted student topic practice score and updates the summary best score',
  function () {
    $this->institution->institutionUsers()->delete();
    $course = Course::factory()
      ->withInstitution($this->institution)
      ->create();
    $classification = Classification::factory()
      ->withInstitution($this->institution)
      ->create();
    $student = Student::factory()
      ->withInstitution($this->institution, $classification)
      ->create();
    $topic = Topic::factory()
      ->course($course)
      ->classificationGroup($classification->classificationGroup)
      ->create();
    $summary = TopicPracticeSummary::query()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'classification_id' => $classification->id,
      'course_id' => $course->id,
      'topic_id' => $topic->id,
      'attempts_count' => 1,
      'latest_questions_count' => 2
    ]);
    $attempt = TopicPracticeAttempt::query()->create([
      'topic_practice_summary_id' => $summary->id,
      'institution_id' => $this->institution->id,
      'student_id' => $student->id,
      'classification_id' => $classification->id,
      'course_id' => $course->id,
      'topic_id' => $topic->id,
      'attempt_number' => 1,
      'questions_count' => 2,
      'questions' => [
        [
          'question' => 'One?',
          'option_a' => 'A',
          'option_b' => 'B',
          'option_c' => 'C',
          'option_d' => 'D',
          'answer' => 'A'
        ],
        [
          'question' => 'Two?',
          'option_a' => 'A',
          'option_b' => 'B',
          'option_c' => 'C',
          'option_d' => 'D',
          'answer' => 'C'
        ]
      ]
    ]);

    actingAs($student->user)
      ->postJson(
        instRoute('courses.practice-questions.submit', [], $this->institution),
        [
          'attempt_id' => $attempt->id,
          'answers' => [0 => 'option_a', 1 => 'option_b']
        ]
      )
      ->assertOk()
      ->assertJsonPath('attempt.score', 1)
      ->assertJsonPath('attempt.questions_count', 2)
      ->assertJsonPath('attempt.percentage', 50);

    assertDatabaseHas('topic_practice_summaries', [
      'id' => $summary->id,
      'latest_score' => 1,
      'latest_questions_count' => 2,
      'best_score' => 1,
      'best_questions_count' => 2
    ]);
  }
);

it(
  'shows teacher topic practice progress with attempted and missing students',
  function () {
    $course = Course::factory()
      ->withInstitution($this->institution)
      ->create();
    $classification = Classification::factory()
      ->withInstitution($this->institution)
      ->create();
    $topic = Topic::factory()
      ->course($course)
      ->classificationGroup($classification->classificationGroup)
      ->create();
    $attemptedStudent = Student::factory()
      ->withInstitution($this->institution, $classification)
      ->create();
    Student::factory()
      ->withInstitution($this->institution, $classification)
      ->create();
    $summary = TopicPracticeSummary::query()->create([
      'institution_id' => $this->institution->id,
      'student_id' => $attemptedStudent->id,
      'classification_id' => $classification->id,
      'course_id' => $course->id,
      'topic_id' => $topic->id,
      'attempts_count' => 1,
      'latest_score' => 8,
      'latest_questions_count' => 10,
      'latest_percentage' => 80,
      'best_score' => 8,
      'best_questions_count' => 10,
      'best_percentage' => 80
    ]);
    TopicPracticeAttempt::query()->create([
      'topic_practice_summary_id' => $summary->id,
      'institution_id' => $this->institution->id,
      'student_id' => $attemptedStudent->id,
      'classification_id' => $classification->id,
      'course_id' => $course->id,
      'topic_id' => $topic->id,
      'attempt_number' => 1,
      'questions' => [],
      'questions_count' => 10,
      'score' => 8,
      'percentage' => 80
    ]);

    actingAs($this->admin)
      ->get(
        instRoute(
          'courses.practice-progress',
          [
            'topic_id' => $topic->id,
            'classification_id' => $classification->id
          ],
          $this->institution
        )
      )
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/courses/practice-progress-teacher')
          ->has('students', 2)
          ->where('selectedTopic.id', $topic->id)
          ->where('selectedClassificationId', $classification->id)
      );
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
