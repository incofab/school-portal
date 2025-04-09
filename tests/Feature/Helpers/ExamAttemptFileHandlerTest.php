<?php

use App\Helpers\ExamAttemptFileHandler;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Support\Facades\File;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->event = Event::factory()
    ->institution($this->institution)
    ->started()
    ->eventCourseables(2)
    ->create();
  $this->eventCourseables = $this->event->eventCourseables;
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->exam = Exam::factory()
    ->started()
    ->examable($this->student)
    ->event($this->event)
    ->started()
    ->create(['num_of_questions' => 5]);
  $this->examCourseable = ExamCourseable::factory()
    ->exam($this->exam)
    ->courseable($this->eventCourseables->first()->courseable)
    ->create(['num_of_questions' => 5]);

  //   $this->examData = $this->exam->only([
  //     'id',
  //     'event_id',
  //     'exam_no',
  //     'time_remaining',
  //     'start_time',
  //     'pause_time',
  //     'end_time',
  //     'status',
  //     'num_of_questions'
  //   ]);
  $this->examAttemptFileHandler = ExamAttemptFileHandler::make($this->exam);
  $this->filePath = $this->examAttemptFileHandler->getFullFilepath();
  //   $this->baseFolder =
  //     __DIR__ . "/../../public/exams/event_{$this->exam->event_id}";
  //   $this->filePath = "$this->baseFolder/exam_{$this->exam->exam_no}.edr";
});

afterEach(function () {
  if (File::exists($this->filePath)) {
    File::delete($this->filePath);
  }
  //   if (File::exists($this->baseFolder)) {
  //     File::deleteDirectory($this->baseFolder);
  //   }
});

it('creates an exam file', function () {
  $result = $this->examAttemptFileHandler->syncExamFile();

  expect($result['success'])->toBeTrue();
  expect(File::exists($this->filePath))->toBeTrue();

  $fileContent = json_decode(File::get($this->filePath), true);
  expect($fileContent['exam'])->toEqual($this->exam->toArray());
  expect($fileContent['attempts'])->toBeEmpty();
});

it('updates an existing exam file', function () {
  $this->examAttemptFileHandler->syncExamFile();
  $newExamData = $this->exam->toArray();
  $newExamData['status'] = 'Active';
  $newExamData['time_remaining'] = 100;
  $newExamAttemptFileHandler = ExamAttemptFileHandler::make($newExamData);
  $result = $newExamAttemptFileHandler->syncExamFile();

  expect($result['success'])->toBeTrue();
  expect(File::exists($this->filePath))->toBeTrue();

  $fileContent = json_decode(File::get($this->filePath), true);
  expect($fileContent['exam'])->toEqual($newExamData);
  expect($fileContent['attempts'])->toBeEmpty();
});

it('gets the content of an existing exam file', function () {
  $this->examAttemptFileHandler->syncExamFile();

  $result = $this->examAttemptFileHandler->getContent();

  expect($result['success'])->toBeTrue();
  expect($result['content']['exam'])->toEqual($this->exam->toArray());
  expect($result['content']['attempts'])->toBeEmpty();
  expect($result['file'])->toBe($this->filePath);
});

it('returns false if exam file does not exist', function () {
  $result = $this->examAttemptFileHandler->getContent();

  expect($result['success'])->toBeFalse();
  expect($result['message'])->toBe('Exam file not found');
  expect($result['exam_not_found'])->toBeTrue();
});

it('returns false if exam time has elapsed', function () {
  $this->exam->update(['end_time' => now()->subSeconds(1000)]);
  $this->examAttemptFileHandler->syncExamFile();
  $result = $this->examAttemptFileHandler->getContent();

  expect($result['success'])->toBeFalse();
  expect($result['message'])->toBe('Time Elapsed');
  expect($result['time_elapsed'])->toBeTrue();
});

it('attempts a question', function () {
  $this->examAttemptFileHandler->syncExamFile();
  $question = Question::factory()
    ->courseable($this->examCourseable->courseable)
    ->create();
  $studentAttempts = [$question->id => 'A'];

  $result = $this->examAttemptFileHandler->attemptQuestion($studentAttempts);

  expect($result['success'])->toBeTrue();
  expect($result['message'])->toBe('Exam file, question attempt recorded');

  $fileContent = json_decode(File::get($this->filePath), true);
  expect($fileContent['attempts'])->toEqual($studentAttempts);
});

it('calculates score from file when user failed some questions', function () {
  $this->examAttemptFileHandler->syncExamFile();
  $questions = Question::factory(2)
    ->courseable($this->examCourseable->courseable)
    ->create();
  $studentAttempts = $questions
    ->mapWithKeys(fn($item) => [$item->id => $item->answer])
    ->toArray();
  $studentAttempts[$questions[0]->id] = '';
  $this->examAttemptFileHandler->attemptQuestion($studentAttempts);

  $result = $this->examAttemptFileHandler->calculateScoreFromFile(
    $questions->toArray()
  );

  expect($result['success'])->toBeTrue();
  expect($result['score'])->toBe(1);
  expect($result['num_of_questions'])->toBe($questions->count());
});

it(
  'calculates score from file when user got all the questions correct',
  function () {
    $this->examAttemptFileHandler->syncExamFile();
    $questions = Question::factory(2)
      ->courseable($this->examCourseable->courseable)
      ->create();
    $studentAttempts = $questions
      ->mapWithKeys(fn($item) => [$item->id => $item->answer])
      ->toArray();
    $this->examAttemptFileHandler->attemptQuestion($studentAttempts);

    $result = $this->examAttemptFileHandler->calculateScoreFromFile(
      $questions->toArray()
    );

    expect($result['success'])->toBeTrue();
    expect($result['score'])->toBe($questions->count());
    expect($result['num_of_questions'])->toBe($questions->count());
  }
);

it('calculates score from file when no attempts are made', function () {
  $this->examAttemptFileHandler->syncExamFile();
  $questions = Question::factory(2)
    ->courseable($this->examCourseable->courseable)
    ->create();

  $result = $this->examAttemptFileHandler->calculateScoreFromFile($questions);

  expect($result['success'])->toBeTrue();
  expect($result['score'])->toBe(0);
  expect($result['num_of_questions'])->toBe(2);
});

it('deletes the exam file', function () {
  $this->examAttemptFileHandler->syncExamFile();
  expect(File::exists($this->filePath))->toBeTrue();

  $this->examAttemptFileHandler->deleteExamFile();

  expect(File::exists($this->filePath))->toBeFalse();
});

it('gets question attempts', function () {
  $this->examAttemptFileHandler->syncExamFile();
  $questions = Question::factory(2)
    ->courseable($this->examCourseable->courseable)
    ->create();
  $studentAttempts = $questions
    ->mapWithKeys(fn($item) => [$item->id => $item->answer])
    ->toArray();
  $this->examAttemptFileHandler->attemptQuestion($studentAttempts);

  $attempts = $this->examAttemptFileHandler->getQuestionAttempts();

  expect($attempts)->toEqual($studentAttempts);
});

it('gets empty question attempts when no attempts are made', function () {
  $this->examAttemptFileHandler->syncExamFile();

  $attempts = $this->examAttemptFileHandler->getQuestionAttempts();

  expect($attempts)->toBeEmpty();
});
