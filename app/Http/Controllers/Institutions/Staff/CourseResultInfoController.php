<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\ClassResultInfo;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;

class CourseResultInfoController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->only('destroy');
  }

  public function index(Institution $institution)
  {
    $institutionUser = currentInstitutionUser();
    $query = CourseResultInfoUITableFilters::make(
      request()->all(),
      CourseResultInfo::query()->select('course_result_info.*')->distinct()
    )
      ->forTeacher($institutionUser)
      ->filterQuery()
      ->getQuery();

    return inertia('institutions/courses/list-course-result-info', [
      'courseResultInfo' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'course')
          ->latest('course_result_info.id')
      )
    ]);
  }

  public function destroy(
    Institution $institution,
    CourseResultInfo $courseResultInfo
  ) {
    ClassResultInfo::ensureResultIsUnlocked(
      $courseResultInfo->classification_id,
      $courseResultInfo->academic_session_id,
      $courseResultInfo->term,
      (bool) $courseResultInfo->for_mid_term
    );

    $courseResultInfo->courseResultQuery()->delete();
    $courseResultInfo->delete();

    return $this->ok();
  }
}
