<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use App\Support\UITableFilters\CourseTeachersUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourseTeachersController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->only([
      'create',
      'store',
      'edit',
      'delete'
    ]);
  }

  function index(Request $request, Institution $institution, User $user = null)
  {
    $query = ($user
      ? $user->courseTeachers()->getQuery()
      : CourseTeacher::query()
    )->select('course_teachers.*');
    CourseTeachersUITableFilters::make($request->all(), $query)->filterQuery();

    $query = $query->with('course', 'user', 'classification')->latest('id');

    return Inertia::render('institutions/staff/list-course-teachers', [
      'courseTeachers' => paginateFromRequest($query),
      'user' => $user
    ]);
  }

  function search(Request $request)
  {
    $query = CourseTeacher::query()->select('course_teachers.*');
    CourseTeachersUITableFilters::make($request->all(), $query)->filterQuery();
    $query = $query
      ->with('course', 'user', 'classification')
      ->latest('course_teachers.id');

    return response()->json([
      'result' => paginateFromRequest($query)
    ]);
  }

  function create(User $user = null)
  {
    return Inertia::render('institutions/staff/register-course-teacher', [
      'courses' => Course::all(),
      'user' => $user
    ]);
  }

  // function edit(CourseTeacher $courseTeacher)
  // {
  //   $courseTeacher->load('course', 'user', 'classification');
  //   return Inertia::render('institutions/staff/register-course-teacher', [
  //     'courses' => Course::all(),
  //     'user' => $courseTeacher->user,
  //     'courseTeacher' => $courseTeacher
  //   ]);
  // }

  function destroy(Institution $institution, CourseTeacher $courseTeacher)
  {
    $courseTeacher->delete();
    return $this->ok();
  }

  function store(Request $request, Institution $institution, User $user)
  {
    abort_unless($user->isInstitutionTeacher(), 403, 'User must be a teacher');

    $data = $request->validate([
      'classification_ids' => ['required', 'min:1'],
      'classification_ids.*' => [
        'required',
        'integer',
        'exists:classifications,id',
        Rule::exists('classifications', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      // 'course_id' => ['required', 'integer', 'exists:courses,id']
      'course_ids' => ['required', 'array', 'min:1'],
      'course_ids.*' => [
        'required',
        'integer',
        Rule::exists('courses', 'id')->where('institution_id', $institution->id)
      ]
    ]);

    $courseIds = $data['course_ids'];
    $classificationIds = $data['classification_ids'];
    foreach ($courseIds as $key => $courseId) {
      foreach ($classificationIds as $key => $classificationId) {
        $user->courseTeachers()->firstOrCreate([
          'course_id' => $courseId,
          'classification_id' => $classificationId
        ]);
      }
    }

    return $this->ok();
  }
}
