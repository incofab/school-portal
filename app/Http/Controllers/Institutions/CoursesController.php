<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCourseRequest;
use App\Models\Course;
use App\Models\Institution;
use App\Support\UITableFilters\CoursesUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoursesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->only([
      'create',
      'store',
      'edit',
      'update',
      'delete'
    ]);
  }

  function index(Request $request)
  {
    $query = Course::query();
    CoursesUITableFilters::make($request->all(), $query);

    return Inertia::render('institutions/courses/list-courses', [
      'courses' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function search(Request $request)
  {
    $query = Course::query()->when(
      $request->search,
      fn($q, $value) => $q->where('title', $value)
    );
    return response()->json([
      'result' => $query->latest('courses.id')->get()
    ]);
  }

  function create()
  {
    return Inertia::render('institutions/courses/create-edit-course', []);
  }

  function edit(Institution $institution, Course $course)
  {
    abort_unless(
      $course->institution_id === currentInstitution()->id,
      403,
      'Access denied'
    );

    return Inertia::render('institutions/courses/create-edit-course', [
      'course' => $course
    ]);
  }

  function destroy(Institution $institution, Course $course)
  {
    abort_unless(
      $course->institution_id === currentInstitution()->id,
      403,
      'Access denied'
    );

    $course->delete();
    return $this->ok();
  }

  function store(CreateCourseRequest $request)
  {
    $data = $request->validated();
    currentInstitution()
      ->courses()
      ->create($data);
    return $this->ok();
  }

  function update(
    CreateCourseRequest $request,
    Institution $institution,
    Course $course
  ) {
    abort_unless(
      $course->institution_id === currentInstitution()->id,
      403,
      'Access denied'
    );

    $data = $request->validated();
    $course->fill($data)->update();
    return $this->ok();
  }
}
