<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Http\Controllers\Controller;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;

class ListCourseResultInfoController extends Controller
{
  public function __invoke(Institution $institution)
  {
    $query = CourseResultInfoUITableFilters::make(
      request()->all(),
      CourseResultInfo::query()->select('course_result_info.*')
    )
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
}
