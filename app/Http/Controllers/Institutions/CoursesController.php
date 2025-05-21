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
use App\Models\Student;
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

  function index(Request $request, Institution $institution)
  {
    $query = Course::query()
      ->select('courses.*')
      ->with('topics', 'sessions');
    CoursesUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/courses/list-courses', [
      'courses' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function search(Request $request)
  {
    $query = Course::query()->when(
      $request->search,
      fn($q, $value) => $q->where('title', 'LIKE', "%$value%")
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
    $data = $request->validated();
    $course->fill($data)->update();
    return $this->ok();
  }

  //= using A.I
  function generatePracticeQuestions(Request $request)
  {    
    $institutionUser = currentInstitutionUser();

    if($institutionUser->isStudent()){
      $className = $institutionUser->student->classification->classificationGroup->title;
      $className = "of ".$className;
    }else{
      $className = '';
    }
    

    $topicIds = $request->topic_ids;
    $lessonNotes = LessonNote::whereIn('topic_id', $topicIds)->where('status', NoteStatusType::Published->value)->get();

    if(count($lessonNotes) < 1){ 
      return $this->message("No Lesson Notes Found", 401);
    }

    $question = "You are a class teacher $className in a Nigerian Basic Education School. Analyze the following Lesson Notes and generate 20 objective questions aimed at helping the student prepare for upcoming class assessment test. Each question should have 4 options (option_a, option_b, option_c, option_d) where only one option is the correct answer. Return the response as an JSON Object, where each object's-item contains the following keys: 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer'. The value of the 'answer' should indicate the correct option (a,b,c,d - NOT 'option_a', 'option_b', 'option_c', 'option_d'). Do not include comments, side comments, stylings, meta tags, etc. Here are the lesson Notes :: $lessonNotes";
    
    $res = GoogleAiHelper::ask($question);

    $res_parts = $res['candidates'][0]['content']['parts'];
    $resQuestions = '';

    foreach ($res_parts as $res_part) {
      $resQuestions .= $res_part['text'];
    }

    /*
    $resQuestions = '```json
    [
      {
        "question": "Which part of speech names a person, place, thing, or idea?",
        "option_a": "Verb",
        "option_b": "Noun",
        "option_c": "Adjective",
        "option_d": "Adverb",
        "answer": "b"
      },
      {
        "question": "Which of the following is NOT a type of noun?",
        "option_a": "Common Noun",
        "option_b": "Proper Noun",
        "option_c": "Helping Noun",
        "option_d": "Abstract Noun",
        "answer": "c"
      },
      {
        "question": "Which of these is an example of a proper noun?",
        "option_a": "Book",
        "option_b": "City",
        "option_c": "Lagos",
        "option_d": "Girl",
        "answer": "c"
      },
      {
        "question": "What type of noun is \'love\'?",
        "option_a": "Concrete Noun",
        "option_b": "Common Noun",
        "option_c": "Proper Noun",
        "option_d": "Abstract Noun",
        "answer": "d"
      },
      {
        "question": "Which part of speech replaces a noun to avoid repetition?",
        "option_a": "Verb",
        "option_b": "Pronoun",
        "option_c": "Adjective",
        "option_d": "Adverb",
        "answer": "b"
      },
      {
        "question": "Which of the following is a possessive pronoun?",
        "option_a": "I",
        "option_b": "Me",
        "option_c": "Mine",
        "option_d": "Myself",
        "answer": "c"
      },
      {
        "question": "Which type of pronoun is used to ask questions?",
        "option_a": "Personal Pronoun",
        "option_b": "Reflexive Pronoun",
        "option_c": "Interrogative Pronoun",
        "option_d": "Demonstrative Pronoun",
        "answer": "c"
      },
      {
        "question": "What part of speech describes an action, occurrence, or state of being?",
        "option_a": "Noun",
        "option_b": "Pronoun",
        "option_c": "Verb",
        "option_d": "Adjective",
        "answer": "c"
      },
      {
        "question": "Which of these is an example of a linking verb?",
        "option_a": "Run",
        "option_b": "Jump",
        "option_c": "Is",
        "option_d": "Write",
        "answer": "c"
      },
      {
        "question": "A verb that takes a direct object is called a?",
        "option_a": "Intransitive Verb",
        "option_b": "Linking Verb",
        "option_c": "Transitive Verb",
        "option_d": "Helping Verb",
        "answer": "c"
      },
      {
        "question": "Which part of speech describes a noun or pronoun?",
        "option_a": "Adverb",
        "option_b": "Adjective",
        "option_c": "Verb",
        "option_d": "Pronoun",
        "answer": "b"
      },
      {
        "question": "Which of the following is a descriptive adjective?",
        "option_a": "One",
        "option_b": "This",
        "option_c": "My",
        "option_d": "Beautiful",
        "answer": "d"
      },
      {
        "question": "What type of adjective is \'Nigerian\' in \'Nigerian food\'?",
        "option_a": "Descriptive Adjective",
        "option_b": "Quantitative Adjective",
        "option_c": "Proper Adjective",
        "option_d": "Possessive Adjective",
        "answer": "c"
      },
      {
        "question": "Which part of speech modifies a verb, adjective, or another adverb?",
        "option_a": "Noun",
        "option_b": "Adverb",
        "option_c": "Adjective",
        "option_d": "Preposition",
        "answer": "b"
      },
      {
        "question": "Which of these adverbs indicates \'when\' something happens?",
        "option_a": "Quickly",
        "option_b": "Here",
        "option_c": "Tomorrow",
        "option_d": "Always",
        "answer": "c"
      },
      {
        "question": "What part of speech shows the relationship between a noun or pronoun and other words?",
        "option_a": "Conjunction",
        "option_b": "Interjection",
        "option_c": "Preposition",
        "option_d": "Adverb",
        "answer": "c"
      },
      {
        "question": "Which of the following is a preposition?",
        "option_a": "And",
        "option_b": "But",
        "option_c": "In",
        "option_d": "Wow",
        "answer": "c"
      },
      {
        "question": "Which part of speech connects words, phrases, or clauses?",
        "option_a": "Preposition",
        "option_b": "Conjunction",
        "option_c": "Interjection",
        "option_d": "Adverb",
        "answer": "b"
      },
      {
        "question": "Which of the following is a coordinating conjunction?",
        "option_a": "Because",
        "option_b": "Although",
        "option_c": "But",
        "option_d": "Since",
        "answer": "c"
      },
      {
        "question": "What part of speech expresses strong emotion or sudden feeling?",
        "option_a": "Noun",
        "option_b": "Verb",
        "option_c": "Adjective",
        "option_d": "Interjection",
        "answer": "d"
      }
    ]
    ```';
    */
    

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

  function viewPracticeQuestions()
  {
    $practiceData = Session::get('practiceData', []);

    $course = $practiceData['course'];
    $practiceQuestions = $practiceData['practiceQuestions'];

    if(count($practiceQuestions) < 1){
      abort('404', 'No Receipt Found');
    }

    $institutionUser = currentInstitutionUser();

    return Inertia::render($institutionUser->isStaff() ? 'institutions/courses/practice-questions-teacher' : 'institutions/courses/practice-questions-student', [
      'course' => $course,
      'practiceQuestions' => $practiceQuestions
    ]);
  }

  function insertQuestionsToQuestionbank(
    Institution $institution,
    CourseSession $courseSession,
    Request $request
  ) {
    $questions = $request->questions;

    $lastQuestion = $courseSession->questions()->latest('question_no')->first();
    $nextQuestionNo = intval($lastQuestion?->question_no) + 1;

    foreach ($questions as $index => $question) {
      $questions[$index]["question_no"] = $nextQuestionNo + $index;
    }

    Question::multiInsert($courseSession, $questions);
    return $this->ok();
  }
}
