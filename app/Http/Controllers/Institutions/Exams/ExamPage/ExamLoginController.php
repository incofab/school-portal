<?php

namespace App\Http\Controllers\Institutions\Exams\ExamPage;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Inertia\Inertia;

class ExamLoginController extends Controller
{
  function __invoke(Institution $institution)
  {
    return Inertia::render('institutions/exams/exam-page/exam-login', [
      'institution' => $institution
    ]);
  }
}
