<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use App\Support\UITableFilters\TermResultUITableFilters;

class ViewResultSheetController extends Controller
{
  public function __invoke(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    TermType $term
  ) {
    $institutionUser = currentInstitutionUser();
    abort_if(
      $institutionUser->user_id !== $student->user_id &&
        !$institutionUser->isStaff(),
      403
    );
    $params = [
      'institution_id' => $institution->id,
      'classification' => $classification->id,
      'term' => $term,
      'academicSession' => $academicSession->id
    ];

    $courseResults = CourseResultsUITableFilters::make(
      $params,
      $student->courseResults()->getQuery()
    )
      ->filterQuery()
      ->getQuery()
      ->with('course', 'teacher')
      ->get();

    $termResult = TermResultUITableFilters::make(
      $params,
      $student->termResults()->getQuery()
    )
      ->filterQuery()
      ->getQuery()
      ->first();

    $courseResultInfo = CourseResultInfoUITableFilters::make(
      $params,
      CourseResultInfo::query()
    )
      ->filterQuery()
      ->getQuery()
      ->get();
    $courseResultInfoData = [];
    foreach ($courseResultInfo as $key => $value) {
      $courseResultInfoData[$value->course_id] = $value;
    }

    $classResultInfo = ClassResultInfoUITableFilters::make(
      $params,
      ClassResultInfo::query()
    )
      ->filterQuery()
      ->getQuery()
      ->first();

    return inertia('institutions/students/result-sheet', [
      'courseResults' => $courseResults,
      'student' => $student->load('user'),
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'termResult' => $termResult,
      'classResultInfo' => $classResultInfo,
      'courseResultInfoData' => $courseResultInfoData
    ]);
  }
}
