<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Enums\ExamStatus;
use App\Helpers\ExamAttemptFileHandler;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\TokenUser;
use App\Support\ExamHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestDisplayExamPageController extends Controller
{
  function __invoke(Institution $institution, Exam $exam, Request $request)
  {
    $exam = $exam
      ->where('id', $exam->id)
      ->with('event')
      ->with(
        'examCourseables.courseable',
        fn($q) => $q->with('course', 'questions', 'passages', 'instructions')
      )
      ->first();
    $exam
      ->fill([
        'start_time' => now(),
        'pause_time' => null,
        'end_time' => $exam->event->getDurationInSeconds(),
        'time_remaining' => null,
        'status' => ExamStatus::Active
      ])
      ->save();

    // $examHandler = ExamHandler::make($exam)->startExam();
    $examAttemptFileHandler = ExamAttemptFileHandler::make($exam);

    return Inertia::render('institutions/exams/exam-page/display-exam', [
      'exam' => $exam,
      'timeRemaining' => $exam->event->getDurationInSeconds(),
      'tokenUser' => new TokenUser([
        'reference' => 'unique_reference',
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'phone' => '07036098561'
      ]),
      'existingAttempts' =>
        collect($exam->attempts)->toArray() +
        $examAttemptFileHandler->getQuestionAttempts()
    ]);
  }
}
