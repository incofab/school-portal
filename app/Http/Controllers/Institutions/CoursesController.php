<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Enums\NoteStatusType;
use App\Helpers\GoogleAiHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\CreateCourseRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\LessonNote;
use App\Models\Question;
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
      'destroy'
    ]);
  }

  function index(Institution $institution, Request $request)
  {
    $query = Course::query()
      ->select('courses.*')
      ->with('topics', 'sessions');
    CoursesUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/courses/list-courses', [
      'courses' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function search(Institution $institution, Request $request)
  {
    $query = Course::query()->when(
      $request->search,
      fn($q, $value) => $q->where('title', 'LIKE', "%$value%")
    );
    return response()->json([
      'result' => $query->latest('courses.id')->get()
    ]);
  }

  function create(Institution $institution)
  {
    return Inertia::render('institutions/courses/create-edit-course', []);
  }

  function edit(Institution $institution, Course $course)
  {
    return Inertia::render('institutions/courses/create-edit-course', [
      'course' => $course
    ]);
  }

  function destroy(Institution $institution, Course $course)
  {
    $course->courseTeachers()->delete();
    $course->delete();
    return $this->ok();
  }

  function store(Institution $institution, CreateCourseRequest $request)
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
    $data = $request->validated();
    $course->fill($data)->update();
    return $this->ok();
  }

  //= using A.I
  function generatePracticeQuestions(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isStudent()) {
      $className =
        $institutionUser->student->classification->classificationGroup->title;
      $className = 'of ' . $className;
    } else {
      $className = '';
    }

    $topicIds = $request->topic_ids;
    $lessonNotes = LessonNote::whereIn('topic_id', $topicIds)
      ->where('status', NoteStatusType::Published->value)
      ->get();

    if (count($lessonNotes) < 1) {
      return $this->message(
        'You have to set Lesson Notes for this topic first',
        401
      );
    }

    $question = "You are a class teacher $className in a Nigerian Basic Education School. Analyze the following Lesson Notes and generate 20 objective questions aimed at helping the student prepare for upcoming class assessment test. Each question should have 4 options (option_a, option_b, option_c, option_d) where only one option is the correct answer. Return the response as an JSON Object, where each object's-item contains the following keys: 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer'. The value of the 'answer' should indicate the correct option (a,b,c,d - NOT 'option_a', 'option_b', 'option_c', 'option_d'). Do not include comments, side comments, stylings, meta tags, etc. Here are the lesson Notes :: $lessonNotes";

    $res = GoogleAiHelper::ask($question);

    $res_parts = $res['candidates'][0]['content']['parts'];
    $resQuestions = '';

    foreach ($res_parts as $res_part) {
      $resQuestions .= $res_part['text'];
    }

    $practiceQuestions = str_replace('```json', '', $resQuestions);
    $practiceQuestions = str_replace('```', '', $practiceQuestions);

    $practiceQuestions = json_decode($practiceQuestions, true);

    $practiceData = [
      'course' => $request->course,
      'practiceQuestions' => $practiceQuestions
    ];

    // Set a session variable
    Session::put('practiceData', $practiceData);

    return $this->ok();
    // return $this->ok(['practice_questions' => $practiceQuestions]);
  }

  function viewPracticeQuestions(Institution $institution)
  {
    $practiceData = Session::get('practiceData', []);

    $course = $practiceData['course'];
    $practiceQuestions = $practiceData['practiceQuestions'];

    if (count($practiceQuestions) < 1) {
      abort('404', 'No Receipt Found');
    }

    $institutionUser = currentInstitutionUser();

    return Inertia::render(
      $institutionUser->isStaff()
        ? 'institutions/courses/practice-questions-teacher'
        : 'institutions/courses/practice-questions-student',
      [
        'course' => $course,
        'practiceQuestions' => $practiceQuestions
      ]
    );
  }

  function insertQuestionsToQuestionbank(
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
    return $this->ok();
  }
}
