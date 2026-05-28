<?php

use App\Models\AcademicSession;
use App\Models\Institution;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\EventCourseable;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;

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
    ->courseable($this->courseSession)
    ->create();
  $this->courseable = [
    CourseSession::class => $this->courseSession,
    EventCourseable::class => $this->eventCourseable
  ];
  // dd($this->eventCourseable->event->toArray());
  $this->topic = Topic::factory()
    ->course($this->course)
    ->create();

  $this->assignedTeacher = User::factory()
    ->teacher($this->institution)
    ->create();
  CourseTeacher::factory()->create([
    'institution_id' => $this->institution->id,
    'course_id' => $this->course->id,
    'user_id' => $this->assignedTeacher->id
  ]);

  $this->otherTeacher = User::factory()
    ->teacher($this->institution)
    ->create();
});

test('index displays questions for a course session', function ($class) {
  $courseable = $this->courseable[$class];
  // dd($class, $courseable->event->toArray());
  $questions = Question::factory(3)
    ->courseable($courseable, $this->institution)
    ->create();

  $response = actingAs($this->instAdmin)->get(
    route('institutions.questions.index', [
      $this->institution,
      $courseable->getMorphedId()
    ])
  );

  $response->assertOk();
  $response->assertViewIs('ccd.questions.index');
  $response->assertViewHas('allRecords');
  $response->assertViewHas('courseable');
  expect($response['allRecords']->count())->toBe(3);
  // }); //->with([EventCourseable::factory()->create()]);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new question via API', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Question::factory()
    ->courseable($courseable, $this->institution)
    ->raw();

  $response = actingAs($this->instAdmin)->postJson(
    route('institutions.api.questions.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    $data
  );

  $response->assertOk();
  $response->assertJson(['success' => true]);
  expect(Question::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new question via API payload file', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Question::factory()
    ->courseable($courseable, $this->institution)
    ->raw();

  $file = UploadedFile::fake()->createWithContent(
    'question.txt',
    json_encode($data)
  );

  $response = actingAs($this->instAdmin)->post(
    route('institutions.api.questions.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    ['question_payload' => $file]
  );

  $response->assertOk();
  $response->assertJson(['success' => true]);
  expect(Question::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new question', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Question::factory()
    ->courseable($courseable, $this->institution)
    ->raw();

  $response = actingAs($this->instAdmin)->post(
    route('institutions.questions.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    $data
  );
  $response->assertRedirect();
  expect(Question::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('store creates a new question via payload file', function ($class) {
  $courseable = $this->courseable[$class];
  $data = Question::factory()
    ->courseable($courseable, $this->institution)
    ->raw();

  $file = UploadedFile::fake()->createWithContent(
    'question.txt',
    json_encode($data)
  );

  $response = actingAs($this->instAdmin)->post(
    route('institutions.questions.store', [
      $this->institution,
      $courseable->getMorphedId()
    ]),
    ['question_payload' => $file]
  );
  $response->assertRedirect();
  expect(Question::count())->toBe(1);
})->with([[CourseSession::class], [EventCourseable::class]]);

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
  $response->assertViewHas('courseable');
  $response->assertViewHas('questionNo', $question->question_no);
})->with([[CourseSession::class], [EventCourseable::class]]);

test('updates an existing question', function ($class) {
  $courseable = $this->courseable[$class];
  $question = Question::factory()
    ->courseable($courseable, $this->institution)
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
})->with([[CourseSession::class], [EventCourseable::class]]);

test('updates an existing question via payload file', function ($class) {
  $courseable = $this->courseable[$class];
  $question = Question::factory()
    ->courseable($courseable, $this->institution)
    ->create();

  $newData = Question::factory()->raw([
    'question' => 'Updated Question Text',
    'institution_id' => $this->institution->id,
    'question_no' => $question->question_no
  ]);

  $file = UploadedFile::fake()->createWithContent(
    'question.txt',
    json_encode($newData)
  );

  $response = actingAs($this->instAdmin)->put(
    route('institutions.questions.update', [$this->institution, $question]),
    ['question_payload' => $file]
  );

  $response->assertRedirect();
  expect(Question::first()->question)->toBe('Updated Question Text');
})->with([[CourseSession::class], [EventCourseable::class]]);

test(
  'assigned course teacher can access question upload and download routes',
  function ($class) {
    $courseable = $this->courseable[$class];
    Question::factory(2)
      ->courseable($courseable, $this->institution)
      ->create();

    actingAs($this->assignedTeacher)
      ->get(
        route('institutions.questions.upload.create', [
          $this->institution,
          $courseable->getMorphedId()
        ])
      )
      ->assertOk();

    actingAs($this->assignedTeacher)
      ->get(
        route('institutions.questions.download', [
          $this->institution,
          $courseable->getMorphedId()
        ])
      )
      ->assertOk();
  }
)->with([[CourseSession::class], [EventCourseable::class]]);

test('unassigned teacher cannot access question bank routes', function (
  $class
) {
  $courseable = $this->courseable[$class];
  $question = Question::factory()
    ->courseable($courseable, $this->institution)
    ->create();

  actingAs($this->otherTeacher)
    ->get(
      route('institutions.questions.index', [
        $this->institution,
        $courseable->getMorphedId()
      ])
    )
    ->assertForbidden();

  actingAs($this->otherTeacher)
    ->post(
      route('institutions.questions.store', [
        $this->institution,
        $courseable->getMorphedId()
      ]),
      Question::factory()
        ->courseable($courseable, $this->institution)
        ->raw()
    )
    ->assertForbidden();

  actingAs($this->otherTeacher)
    ->get(route('institutions.questions.edit', [$this->institution, $question]))
    ->assertForbidden();

  actingAs($this->otherTeacher)
    ->get(
      route('institutions.questions.upload.create', [
        $this->institution,
        $courseable->getMorphedId()
      ])
    )
    ->assertForbidden();

  actingAs($this->otherTeacher)
    ->get(
      route('institutions.questions.download', [
        $this->institution,
        $courseable->getMorphedId()
      ])
    )
    ->assertForbidden();
})->with([[CourseSession::class], [EventCourseable::class]]);
