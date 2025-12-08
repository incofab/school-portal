<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;
use App\Support\UITableFilters\TermResultUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListStudentTermResultController extends Controller
{
  public function __invoke(
    Institution $institution,
    Student $student,
    Request $request
  ) {
    $query = $student
      ->termResults()
      ->getQuery()
      ->activated()
      ->select('term_results.*')
      ->latest('term_results.academic_session_id')
      ->latest('term_results.id');

    TermResultUITableFilters::make($request->all(), $query)
      ->dontUseCurrentTerm()
      ->filterQuery();

    return Inertia::render('institutions/results/list-term-results', [
      'student' => $student,
      'termResults' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'student.user')
          ->oldest('term_results.position')
      )
    ]);
  }
}
