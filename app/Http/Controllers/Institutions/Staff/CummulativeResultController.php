<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\FormatCummulativeResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\CummulativeResultRequest;
use App\Models\Institution;

class CummulativeResultController extends Controller
{
  public function __invoke(
    Institution $institution,
    CummulativeResultRequest $request
  ) {
    $formatedCummulativeResult = [];
    $classification = $request->classificationObj;
    $academicSession = $request->academicSessionObj;
    $term = $request->term;
    if ($classification && $academicSession) {
      $formatedCummulativeResult = FormatCummulativeResult::run(
        $academicSession,
        $classification,
        $term
      );
    }
    // dd($formatedCummulativeResult);
    return inertia('institutions/staff/cummulative-result-sheet', [
      ...$formatedCummulativeResult,
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term
    ]);
  }
}
