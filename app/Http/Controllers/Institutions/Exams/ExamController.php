<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Institution;
use App\Rules\ValidateMorphRule;
use App\Support\ExamHandler;
use App\Support\MorphMap;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Inertia\Inertia;

class ExamController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  function index(Institution $institution, Event $event, Request $request)
  {
    $query = $event
      ->exams()
      ->getQuery()
      ->withCount('examCourseables');

    return Inertia::render('institutions/exams/list-exams', [
      'exams' => paginateFromRequest($query->latest('id')),
      'event' => $event
    ]);
  }

  function create(Institution $institution, Event $event)
  {
    return Inertia::render('institutions/exams/create-exam', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'external_reference' => request('reference')
    ]);
  }

  function store(Request $request, Institution $institution, Event $event)
  {
    $student = currentInstitutionUser()->student;
    $data = $request->validate([
      'start_now' => ['nullable', 'boolean'],
      'external_reference' => [
        'string',
        new RequiredIf(empty($student) && !config('app.debug'))
      ],
      'courseables' => ['required', 'array', 'min:1'],
      'courseables.*.courseable_id' => ['required', 'integer'],
      'courseables.*.courseable_type' => [
        'required',
        new ValidateMorphRule('courseable'),
        Rule::in(MorphMap::keys([CourseSession::class]))
      ]
    ]);

    $examData = [
      'institution_id' => $institution->id,
      'event_id' => $event->id,
      ...$student ? ['student_id' => $student->id] : [],
      ...$request->external_reference
        ? ['external_reference' => $data['external_reference']]
        : []
    ];

    DB::beginTransaction();
    $exam = $event->exams()->firstOrCreate($examData, [
      'exam_no' => Exam::generateExamNo()
    ]);

    foreach ($data['courseables'] as $key => $courseable) {
      $exam->examCourseables()->firstOrCreate($courseable);
    }
    if ($request->start_now) {
      ExamHandler::make($exam)->startExam();
    }
    DB::commit();

    return $this->ok(['exam' => $exam]);
  }

  function destroy(Institution $institution, Exam $exam)
  {
    $exam->delete();
    return $this->ok();
  }
}
