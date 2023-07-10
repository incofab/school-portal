<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\ClassResultInfoAction;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class ClassResultInfoController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Request $request)
  {
    $query = ClassResultInfo::query()->select('class_result_info.*');
    ClassResultInfoUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/courses/list-class-result-info', [
      'classResultInfo' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification')
          ->latest('class_result_info.id')
      )
    ]);
  }

  public function calculate(
    Institution $institution,
    Classification $classification,
    Request $request
  ) {
    $request->validate([
      'academic_session_id' => ['required', 'exists:academic_sessions,id'],
      'term' => ['required', new Enum(TermType::class)],
      'for_mid_term' => ['required', 'boolean']
    ]);

    ClassResultInfoAction::make()->calculate(
      $classification,
      $request->academic_session_id,
      $request->term,
      $request->for_mid_term
    );
    return $this->ok();
  }

  public function reCalculate(
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    ClassResultInfoAction::make()->reCalculate($classResultInfo);
    return $this->ok();
  }
}
