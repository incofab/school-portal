<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Actions\CreateExam;
use App\Enums\EventStatus;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except(
      'create',
      'store'
    );
    $this->allowedRoles([InstitutionUserType::Student])->only(
      'create',
      'store'
    );
  }

  function index(Institution $institution, Event $event, Request $request)
  {
    $query = $event
      ->exams()
      ->getQuery()
      ->with('examable')
      ->withCount('examCourseables');

    return Inertia::render('institutions/exams/list-exams', [
      'exams' => paginateFromRequest($query->latest('id')),
      'event' => $event
    ]);
  }

  private function validateCreateExam(Event $event)
  {
    [$status, $message] = $event->canCreateExamCheck();
    abort_unless($status, 400, $message);
  }

  function create(Institution $institution, Event $event)
  {
    $this->validateCreateExam($event);
    $student = currentInstitutionUser()->student;

    if ($event->eventCourseables->count() == 1) {
      // start exam and go to exam page
      $eventCourseable = $event->eventCourseables->first();
      $exam = CreateExam::run($event, [
        'start_now' => true,
        'examable_id' => $student->id,
        'examable_type' => MorphMap::key(Student::class),
        'courseables' => [
          [
            'courseable_id' => $eventCourseable->courseable_id,
            'courseable_type' => $eventCourseable->courseable_type
          ]
        ]
      ]);
      return redirect(
        route('institutions.display-exam-page', [
          $institution->uuid,
          $exam->exam_no
        ])
      );
    }

    return Inertia::render('institutions/exams/create-exam', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'external_reference' => request('reference'),
      'examable_type' => MorphMap::key(User::class),
      'student' => currentInstitutionUser()?->student
    ]);
  }

  function store(
    StoreExamRequest $request,
    Institution $institution,
    Event $event
  ) {
    $this->validateCreateExam($event);
    $exam = CreateExam::run($event, $request->validated());
    return $this->ok(['exam' => $exam]);
  }

  function destroy(Institution $institution, Exam $exam)
  {
    $exam->delete();
    return $this->ok();
  }
}
