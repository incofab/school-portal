<?php

use App\Models\AcademicSession;
use App\Models\Institution;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Question;
use App\Models\Topic;

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

  $this->topic = Topic::factory()
    ->course($this->course)
    ->create();
});

test('index displays questions for a course session', function () {
  $questions = Question::factory(3)
    ->courseable($this->courseSession)
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.questions.index', [
      $this->institution,
      $this->courseSession
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.questions.index');
  $response->assertViewHas('allRecords');
  $response->assertViewHas('courseSession');
  expect($response['allRecords']->count())->toBe(3);
});

test('store creates a new question via API', function () {
  $data = Question::factory()
    ->courseable($this->courseSession)
    ->raw();

  $response = actingAs($this->instAdmin)->postJson(
    route('institutions.api.questions.store', [
      $this->institution,
      $this->courseSession
    ]),
    $data
  );

  $response->assertOk();
  $response->assertJson(['success' => true]);
  expect(Question::count())->toBe(1);
});

test('store creates a new question', function () {
  $data = Question::factory()
    ->courseable($this->courseSession)
    ->raw();

  $response = actingAs($this->instAdmin)->post(
    route('institutions.questions.store', [
      $this->institution,
      $this->courseSession
    ]),
    $data
  );
  $response->assertRedirect();
  expect(Question::count())->toBe(1);
});

test('edit displays a form to edit a question', function () {
  $question = Question::factory()
    ->courseable($this->courseSession)
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.questions.edit', [$this->institution, $question])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.questions.create-question');
  $response->assertViewHas('edit', $question);
  $response->assertViewHas('courseSession');
  $response->assertViewHas('questionNo', $question->question_no);
  $response->assertViewHas('topics');
  expect($response['topics']->count())->toBe(1);
});

test('updates an existing question', function () {
  $question = Question::factory()
    ->courseable($this->courseSession)
    ->create();

  $newData = Question::factory()->raw([
    'question' => 'Updated Question Text',
    'institution_id' => $this->institution->id
  ]);

  $response = actingAs($this->instAdmin)->put(
    route('institutions.questions.update', [$this->institution, $question]),
    $newData
  );

  $response->assertRedirect();
  expect(Question::first()->question)->toBe('Updated Question Text');
});
