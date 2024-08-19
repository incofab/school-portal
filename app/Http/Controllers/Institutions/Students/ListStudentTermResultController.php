<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\UITableFilters\TermResultUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListStudentTermResultController extends Controller
{
  public function __invoke(Student $student, Request $request)
  {
    $query = $student
      ->termResults()
      ->getQuery()
      ->activated()
      ->select('term_results.*');

    TermResultUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/list-term-results', [
      'student' => $student,
      'termResults' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'student.user')
          ->oldest('term_results.position')
      )
    ]);
  }
}
