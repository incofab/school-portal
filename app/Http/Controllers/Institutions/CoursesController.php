<?php

namespace App\Http\Controllers\Institutions;

use App\Actions\CoursePractice\GenerateTopicPracticeQuestions;
use App\Actions\CoursePractice\GetStudentTopicPracticeProgress;
use App\Actions\CoursePractice\GetTeacherTopicPracticeProgress;
use App\Actions\CoursePractice\SubmitTopicPracticeAttempt;
use App\Enums\Audit\ActivityLogCategory;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Topic;
use App\Models\TopicPracticeAttempt;
use App\Support\Audit\AcademicActivityLogger;
use App\Support\UITableFilters\CoursesUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
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
      'destroy'
    ]);
  }

  public function index(Institution $institution, Request $request)
  {
    $query = Course::query()
      ->select('courses.*')
      ->with('topics', 'sessions');
    CoursesUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/courses/list-courses', [
      'courses' => paginateFromRequest($query->oldest('title'))
    ]);
  }

  public function search(Institution $institution, Request $request)
  {
    $query = Course::query()->when(
      $request->search,
      fn($q, $value) => $q->where('title', 'LIKE', "%$value%")
    );

    return response()->json([
      'result' => $query->latest('courses.id')->get()
    ]);
  }

  public function create(Institution $institution)
  {
    return Inertia::render('institutions/courses/create-edit-course', []);
  }

  public function multiCreate(Institution $institution)
  {
    return Inertia::render('institutions/courses/create-multi-courses', []);
  }

  public function edit(Institution $institution, Course $course)
  {
    return Inertia::render('institutions/courses/create-edit-course', [
      'course' => $course
    ]);
  }

  public function destroy(Institution $institution, Course $course)
  {
    abort_if(
      $course->hasExistingReferences(),
      400,
      'This course cannot be deleted because it has existing references'
    );

    $course->courseTeachers()->delete();
    $course->forceDelete();

    return $this->ok();
  }

  public function store(Institution $institution, Request $request)
  {
    $data = $request->validate(Course::createRule());
    $institution->courses()->create($data);

    return $this->ok();
  }

  public function multiStore(Institution $institution, Request $request)
  {
    $data = $request->validate(Course::createRule(null, 'courses.*.'));
    foreach ($data['courses'] as $key => $value) {
      $institution->courses()->create($value);
    }

    return $this->ok();
  }

  public function update(
    Request $request,
    Institution $institution,
    Course $course
  ) {
    $data = $request->validate(Course::createRule($course));
    $course->fill($data)->update();

    return $this->ok();
  }

  public function generatePracticeQuestions(
    Request $request,
    Institution $institution
  ) {
    $data = $request->validate([
      'topic_ids' => ['required', 'array', 'size:1'],
      'topic_ids.*' => ['required', 'integer']
    ]);
    $institutionUser = currentInstitutionUser();
    $topic = Topic::query()
      ->with('course')
      ->whereKey($data['topic_ids'][0])
      ->firstOrFail();

    Session::put(
      'practiceData',
      GenerateTopicPracticeQuestions::run(
        $institution,
        $institutionUser,
        $topic,
        $request->course
      )
    );

    return $this->ok();
  }

  public function submitPracticeQuestions(
    Request $request,
    Institution $institution
  ) {
    $data = $request->validate([
      'attempt_id' => [
        'required',
        'integer',
        Rule::exists('topic_practice_attempts', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'answers' => ['required', 'array'],
      'answers.*' => ['nullable', 'string']
    ]);

    $attempt = TopicPracticeAttempt::query()
      ->whereKey($data['attempt_id'])
      ->firstOrFail();

    return $this->ok(
      SubmitTopicPracticeAttempt::run(
        currentInstitutionUser(),
        $attempt,
        $data['answers']
      )
    );
  }

  public function viewPracticeQuestions(Institution $institution)
  {
    $practiceData = Session::get('practiceData', []);

    $course = $practiceData['course'];
    $practiceQuestions = $practiceData['practiceQuestions'] ?? [];

    if (!is_array($practiceQuestions) || count($practiceQuestions) < 1) {
      abort('404', 'No Receipt Found');
    }

    $institutionUser = currentInstitutionUser();

    return Inertia::render(
      $institutionUser->isStaff()
        ? 'institutions/courses/practice-questions-teacher'
        : 'institutions/courses/practice-questions-student',
      [
        'course' => $course,
        'topic' => $practiceData['topic'] ?? null,
        'attemptId' => $practiceData['attemptId'] ?? null,
        'practiceSummary' => $practiceData['practiceSummary'] ?? null,
        'practiceQuestions' => $practiceQuestions
      ]
    );
  }

  public function practiceProgress(Institution $institution, Request $request)
  {
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isStudent()) {
      return Inertia::render(
        'institutions/courses/practice-progress-student',
        GetStudentTopicPracticeProgress::run($institutionUser->student)
      );
    }

    abort_unless($institutionUser->isStaff(), 403);

    return Inertia::render(
      'institutions/courses/practice-progress-teacher',
      GetTeacherTopicPracticeProgress::run(
        $request->integer('topic_id') ?: null,
        $request->integer('classification_id') ?: null
      )
    );
  }

  public function insertQuestionsToQuestionbank(
    Institution $institution,
    CourseSession $courseSession,
    Request $request
  ) {
    $questions = $request->questions;

    $lastQuestion = $courseSession
      ->questions()
      ->latest('question_no')
      ->first();
    $nextQuestionNo = intval($lastQuestion?->question_no) + 1;

    foreach ($questions as $index => $question) {
      $questions[$index]['question_no'] = $nextQuestionNo + $index;
    }

    Question::multiInsert($courseSession, $questions);

    $courseSession->loadMissing('course');
    app(AcademicActivityLogger::class)->workflowEvent(
      $institution,
      'question_bank.generated',
      ActivityLogCategory::Course,
      'inserted_generated_questions',
      'Generated questions inserted into question bank.',
      [
        'course_session_id' => $courseSession->id,
        'course_id' => $courseSession->course_id,
        'course_title' => $courseSession->course?->title,
        'session' => $courseSession->session,
        'question_count' => count($questions)
      ],
      $courseSession->course
    );

    return $this->ok();
  }
}
