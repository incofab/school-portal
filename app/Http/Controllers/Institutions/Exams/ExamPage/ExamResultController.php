<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Support\ExamHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamResultController extends Controller
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

    $examHandler = ExamHandler::make($exam);

    abort_if(
      $examHandler->canRun(false),
      403,
      'You cannot view results when exam is still active'
    );

    if ($exam->status !== ExamStatus::Ended) {
      $examHandler->endExam();
    }

    $tokenUser = currentUser() ?? $this->getTokenUserFromCookie();

    return Inertia::render('institutions/exams/exam-page/exam-result', [
      'exam' => $exam,
      'tokenUser' => $tokenUser
    ]);
  }
}
