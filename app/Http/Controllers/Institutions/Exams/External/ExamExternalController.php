<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Actions\CreateExam;
use App\Helpers\InstitutionBackgroundImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Models\AdmissionApplication;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TokenUser;
use App\Support\MorphMap;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamExternalController extends Controller
{
  private function validateCreateExam(Event $event)
  {
    [$status, $message] = $event->canCreateExamCheck();
    abort_unless($status, 403, $message);
  }

  function create(Institution $institution, Event $event)
  {
    $this->validateCreateExam($event);
    $tokenUser = $this->getTokenUserFromCookie();
    return Inertia::render('institutions/exams/external/create-exam-external', [
      'event' => $event->load('eventCourseables.courseable.course'),
      'tokenUser' => $tokenUser,
      'examable_type' => MorphMap::key(TokenUser::class)
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

  function studentExamLoginCreate()
  {
    return Inertia::render('auth/student-exam-login', [
      'imageUrl' => InstitutionBackgroundImage::getBackgroundImage()
    ]);
  }

  function studentExamLoginStore(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'event_code' => ['required', 'string'],
      'student_code' => ['required', 'string']
    ]);

    $event = Event::query()
      ->where('code', $data['event_code'])
      ->with('institution')
      ->firstOrFail();

    $student = Student::query()
      ->where('code', $data['student_code'])
      ->with('institutionUser', 'classification')
      ->firstOrFail();

    abort_unless(
      $event->institution_id === $student->institutionUser->institution_id,
      403,
      'Institution mismatch'
    );

    [$status, $message] = $event->canCreateExamCheck();
    abort_unless($status, 400, $message);

    $exam = CreateExam::make($event, $student, $event->eventCourseables, [
      'start_now' => true
    ])->execute();

    return redirect(
      route('institutions.display-exam-page', [
        $event->institution->uuid,
        $exam->exam_no
      ])
    );
  }

  function admissionExamLoginCreate(Institution $institution)
  {
    return Inertia::render('auth/admissions-exam-login');
  }

  function admissionExamLoginStore(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'event_code' => ['required', 'string'],
      'application_no' => ['required', 'string']
    ]);

    $event = Event::query()
      ->where('code', $data['event_code'])
      ->with('institution')
      ->firstOrFail();

    $admissionApplication = AdmissionApplication::query()
      ->where('application_no', $data['application_no'])
      // ->with('institutionUser')
      ->firstOrFail();

    abort_unless(
      $event->institution_id === $admissionApplication->institution_id,
      403,
      'Institution mismatch'
    );

    [$status, $message] = $event->canCreateExamCheck();
    abort_unless($status, 400, $message);

    $exam = CreateExam::make(
      $event,
      $admissionApplication,
      $event->eventCourseables,
      ['start_now' => true]
    )->execute();

    return redirect(
      route('institutions.display-exam-page', [
        $event->institution->uuid,
        $exam->exam_no
      ])
    );
  }
}
