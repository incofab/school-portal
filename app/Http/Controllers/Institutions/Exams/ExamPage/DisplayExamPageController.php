<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Helpers\ExamAttemptFileHandler;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use App\Support\ExamHandler;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayExamPageController extends Controller
{
  function __invoke(Institution $institution, Exam $exam, Request $request)
  {
    $exam = $exam
      ->where('id', $exam->id)
      ->with('event')
      ->with([
        'examable' => function (MorphTo $morphTo) {
          $morphTo->morphWith([Student::class => ['user']]);
        }
      ])
      ->with(
        'examCourseables.courseable',
        fn($q) => $q->with('course', 'questions', 'passages', 'instructions')
      )
      ->first();

    $examHandler = ExamHandler::make($exam)->startExam();
    $examAttemptFileHandler = ExamAttemptFileHandler::make($exam);
    $tokenUser =
      currentUser() ?? ($exam->examable ?? $this->getTokenUserFromCookie());

    return Inertia::render('institutions/exams/exam-page/display-exam', [
      'exam' => $exam,
      'timeRemaining' => $examHandler->getTimeRemaining(),
      'tokenUser' => $tokenUser,
      'existingAttempts' =>
        collect($exam->attempts)->toArray() +
        $examAttemptFileHandler->getQuestionAttempts()
    ]);
  }
}
