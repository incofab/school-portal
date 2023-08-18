<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Support\ExamHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayExamPageController extends Controller
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

    ExamHandler::make($exam)->startExam();

    return Inertia::render('institutions/exams/exam-page/display-exam', [
      'exam' => $exam
    ]);
  }
}
