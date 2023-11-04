<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use DB;
use Illuminate\Http\Request;

class ShowLeaderBoardController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    $tokenUser = $this->getTokenUserFromCookie();

    $leaderBoardExams = Exam::query()
      ->select(
        'exams.*',
        DB::raw('SUM(score) AS total_score'),
        DB::raw('COUNT(id) AS exam_count')
      )
      ->where('institution_id', $institution->id)
      ->with('examable')
      ->groupBy('examable_id', 'examable_type')
      ->orderBy('total_score', 'desc')
      ->get();

    $exams = $tokenUser
      ->exams()
      ->where('institution_id', $institution->id)
      ->get();

    return inertia('institutions/exams/external/show-leader-board', [
      'leaderBoardExams' => $leaderBoardExams,
      'tokenUser' => $tokenUser,
      'exams' => $exams
    ]);
  }
}
