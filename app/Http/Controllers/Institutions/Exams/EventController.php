<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Actions\DownloadResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassificationGroup;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class EventController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ])->except(
      'index',
      'show'
    );
  }

  function index(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();
    $student = $institutionUser
      ?->student()
      ->with('classification')
      ->first();

    $query = $institution
      ->events()
      ->getQuery()
      ->forStudent($student)
      ->withCount('eventCourseables');

    if ($institutionUser?->isTeacher()) {
      $courseIds = CourseTeacher::query()
        ->where('user_id', $institutionUser->user_id)
        ->pluck('course_id');
      $query->whereHas('eventCourseables', function ($query) use ($courseIds) {
        $query->whereHasMorph(
          'courseable',
          [CourseSession::class],
          fn($query) => $query->whereIn('course_id', $courseIds)
        );
      });
    }

    return Inertia::render('institutions/exams/list-events', [
      'events' => paginateFromRequest($query->latest('id')),
      'assessments' => Assessment::all()
    ]);
  }

  function offlineCbtSetupGuide(Institution $institution)
  {
    return Inertia::render('institutions/exams/offline-cbt-setup-guide', [
      'videoUrl' => 'https://www.youtube.com/watch?v=RpbM29SH9Q0',
      'downloadUrl' => route('download-offline-cbt-app')
    ]);
  }

  function create(Institution $institution)
  {
    $coursesQuery = $institution->courses()->with('sessions');
    if (currentInstitutionUser()->isTeacher()) {
      $coursesQuery->whereIn(
        'courses.id',
        CourseTeacher::query()
          ->where('user_id', currentInstitutionUser()->user_id)
          ->select('course_id')
      );
    }

    return Inertia::render('institutions/exams/create-edit-event', [
      'classificationGroups' => ClassificationGroup::all(),
      'courses' => $coursesQuery->orderedByCourseOrder()->get()
    ]);
  }

  function edit(Institution $institution, Event $event)
  {
    $this->ensureTeacherOwns($event);
    return Inertia::render('institutions/exams/create-edit-event', [
      'event' => $event,
      'classificationGroups' => ClassificationGroup::all()
    ]);
  }

  function show(Institution $institution, Event $event)
  {
    $event->load(
      'eventCourseables.courseable.course',
      'classificationGroup',
      'classification'
    );
    $student = currentInstitutionUser()->student;
    return Inertia::render('institutions/exams/show-event', [
      'event' => $event,
      'studentExam' => $student
        ? $event
          ->exams()
          ->getQuery()
          ->forExamable($student)
          ->with('examCourseables.courseable.course')
          ->first()
        : null
    ]);
  }

  function destroy(Institution $institution, Event $event)
  {
    $this->ensureTeacherOwns($event);
    $event->delete();
    return $this->ok();
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate([
      ...Event::createRule(),
      'event_courseables' => ['nullable', 'array', 'min:1'],
      ...EventCourseable::createRule('event_courseables.*.')
    ]);
    $this->ensureTeacherCanUseCourses($data['event_courseables'] ?? []);
    $event = $institution->events()->create([
      ...collect($data)
        ->except('event_courseables')
        ->toArray(),
      'code' => Event::generateCode()
    ]);
    foreach ($data['event_courseables'] ?? [] as $key => $courseable) {
      $event->eventCourseables()->updateOrCreate($courseable);
    }
    return $this->ok();
  }

  function update(Request $request, Institution $institution, Event $event)
  {
    $this->ensureTeacherOwns($event);
    $data = $request->validate(Event::createRule($event));
    $event
      ->fill([...$data, 'code' => $event->code ?? Event::generateCode()])
      ->save();
    return $this->ok();
  }

  function download(Institution $institution, Event $event)
  {
    $this->ensureTeacherOwns($event);
    $excelWriter = DownloadResult::run($event);
    $fileName = sanitizeFilename("{$event->title}-exams.xlsx");
    $tempFilePath = storage_path("app/public/{$fileName}");
    // Save to a temporary file
    $excelWriter->save($tempFilePath);
    return Response::download($tempFilePath, $fileName)->deleteFileAfterSend(
      true
    );
  }

  private function ensureTeacherOwns(Event $event): void
  {
    $institutionUser = currentInstitutionUser();
    if ($institutionUser->isAdmin()) {
      return;
    }

    $courseIds = CourseTeacher::query()
      ->where('user_id', $institutionUser->user_id)
      ->pluck('course_id');

    abort_unless(
      $event
        ->eventCourseables()
        ->whereHasMorph(
          'courseable',
          [CourseSession::class],
          fn($query) => $query->whereIn('course_id', $courseIds)
        )
        ->exists(),
      403,
      'You can only work on your own exams'
    );
  }

  private function ensureTeacherCanUseCourses(array $courseables): void
  {
    $institutionUser = currentInstitutionUser();
    if ($institutionUser->isAdmin()) {
      return;
    }

    $courseIds = CourseTeacher::query()
      ->where('user_id', $institutionUser->user_id)
      ->pluck('course_id');
    $courseSessionIds = collect($courseables)
      ->pluck('courseable_id')
      ->unique()
      ->values();

    abort_unless(
      $courseSessionIds->isNotEmpty() &&
        CourseSession::query()
          ->whereIn('id', $courseSessionIds)
          ->whereIn('course_id', $courseIds)
          ->count() === $courseSessionIds->count(),
      403,
      'You can only create exams for your assigned subjects'
    );
  }
}
