<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Actions\Exams\StartOrResumeExamAttempt;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayExamPageController extends Controller
{
  function __invoke(
    Institution $institution,
    Exam $exam,
    Request $request,
    StartOrResumeExamAttempt $startOrResumeExamAttempt
  ) {
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
        fn($q) => $q
          ->with('course', 'passages', 'instructions')
          // ->with(['questions'])
          ->with(['questions' => fn($q2) => $q2->inRandomOrder()])
          ->with('theoryQuestions')
      )
      ->first();

    $examHandler = $startOrResumeExamAttempt->execute($exam);
    $tokenUser =
      currentUser() ?? ($exam->examable ?? $this->getTokenUserFromCookie());

    return Inertia::render('institutions/exams/exam-page/display-exam', [
      'exam' => $exam,
      'timeRemaining' => $examHandler->getTimeRemaining(),
      'tokenUser' => $tokenUser,
      'existingAttempts' =>
        collect($exam->attempts)->toArray() +
        $exam
          ->questionAttempts()
          ->get(['questionable_type', 'questionable_id', 'answer'])
          ->mapWithKeys(
            fn($attempt) => [
              $attempt->questionable_type ===
              (new \App\Models\TheoryQuestion())->getMorphClass()
                ? 'theory-' . $attempt->questionable_id
                : $attempt->questionable_id => $attempt->answer
            ]
          )
          ->toArray()
    ]);
  }
}
