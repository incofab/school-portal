<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Rules\ValidateMorphRule;
use App\Support\MorphMap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExamCourseableController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index(Institution $institution, Exam $exam)
  {
    $query = $exam->examCourseables()->getQuery();

    return Inertia::render('institutions/exams/list-exam-courseables', [
      'examCourseables' => paginateFromRequest($query->latest('id')),
      'exam' => $exam->load('event')
    ]);
  }

  function create(Institution $institution, Exam $exam)
  {
    return Inertia::render('institutions/exams/create-edit-exam-courseables', [
      'exam' => $exam,
      'exam_courseables' => $exam
        ->examCourseables()
        ->with('courseable')
        ->get()
    ]);
  }

  function store(Request $request, Exam $exam)
  {
    $morphRule = new ValidateMorphRule('courseable');
    $data = $request->validate([
      'courseables' => ['required', 'array', 'min:1'],
      'courseables.*.courseable_id' => ['required', 'integer'],
      'courseables.*.courseable_type' => [
        'required',
        $morphRule,
        Rule::in(MorphMap::keys([CourseSession::class]))
      ]
    ]);

    foreach ($data['courseables'] as $key => $courseable) {
      $exam->examCourseables()->updateOrCreate($courseable);
    }

    return $this->ok();
  }

  function destroy(Institution $institution, ExamCourseable $examCourseable)
  {
    $examCourseable->delete();
    return $this->ok();
  }
}
