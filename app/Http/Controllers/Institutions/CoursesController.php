<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Enums\NoteStatusType;
use App\Helpers\GoogleAiHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCourseRequest;
use App\Models\Course;
use App\Models\Institution;
use App\Models\LessonNote;
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
    $query = Course::query()->select('courses.*')->with('topics');
    CoursesUITableFilters::make($request->all(), $query)->filterQuery();


    return Inertia::render('institutions/courses/list-courses', [
      'courses' => paginateFromRequest($query->latest('id')),
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
    /*
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

    // $question = "You are a class teacher for $className in a Nigerian Basic Education School. Analyze the following Lesson Notes and generate 20 questions aimed at helping the student prepare for upcoming class assessment test. Give me only the questions, no comment nor side comments. Return the response in pure html and in a numbered list. Do not include stylings, meta tags, etc. Here are the lesson Notes :: $lessonNotes";

    $question = "You are a class teacher $className in a Nigerian Basic Education School. Analyze the following Lesson Notes and generate 20 objective questions aimed at helping the student prepare for upcoming class assessment test. Each question should have 4 options (a,b,c,d) where only one option is the correct answer. Return the response as an Array-of-Arrays/JSON Object, where each child-array/inner-object contains the following keys: 'question', 'a', 'b', 'c', 'd', 'correct_answer'. The value of the 'correct_answer' should indicate the correct option (a,b,c,d). Do not include comments, side comments, stylings, meta tags, etc. Here are the lesson Notes :: $lessonNotes";
    
    $res = GoogleAiHelper::ask($question);

    $res_parts = $res['candidates'][0]['content']['parts'];
    $resQuestions = '';

    foreach ($res_parts as $res_part) {
      $resQuestions .= $res_part['text'];
    }
    */

    $resQuestions = '```json
[
  {
    "question": "Which part of speech names a person, place, thing, or idea?",
    "a": "Verb",
    "b": "Noun",
    "c": "Adjective",
    "d": "Adverb",
    "correct_answer": "b"
  },
  {
    "question": "Which of the following is NOT a type of noun?",
    "a": "Common Noun",
    "b": "Proper Noun",
    "c": "Helping Noun",
    "d": "Abstract Noun",
    "correct_answer": "c"
  },
  {
    "question": "Which of these is an example of a proper noun?",
    "a": "Book",
    "b": "City",
    "c": "Lagos",
    "d": "Girl",
    "correct_answer": "c"
  },
  {
    "question": "What type of noun is \'love\'?",
    "a": "Concrete Noun",
    "b": "Common Noun",
    "c": "Proper Noun",
    "d": "Abstract Noun",
    "correct_answer": "d"
  },
  {
    "question": "Which part of speech replaces a noun to avoid repetition?",
    "a": "Verb",
    "b": "Pronoun",
    "c": "Adjective",
    "d": "Adverb",
    "correct_answer": "b"
  },
  {
    "question": "Which of the following is a possessive pronoun?",
    "a": "I",
    "b": "Me",
    "c": "Mine",
    "d": "Myself",
    "correct_answer": "c"
  },
  {
    "question": "Which type of pronoun is used to ask questions?",
    "a": "Personal Pronoun",
    "b": "Reflexive Pronoun",
    "c": "Interrogative Pronoun",
    "d": "Demonstrative Pronoun",
    "correct_answer": "c"
  },
  {
    "question": "What part of speech describes an action, occurrence, or state of being?",
    "a": "Noun",
    "b": "Pronoun",
    "c": "Verb",
    "d": "Adjective",
    "correct_answer": "c"
  },
  {
    "question": "Which of these is an example of a linking verb?",
    "a": "Run",
    "b": "Jump",
    "c": "Is",
    "d": "Write",
    "correct_answer": "c"
  },
  {
    "question": "A verb that takes a direct object is called a?",
    "a": "Intransitive Verb",
    "b": "Linking Verb",
    "c": "Transitive Verb",
    "d": "Helping Verb",
    "correct_answer": "c"
  },
  {
    "question": "Which part of speech describes a noun or pronoun?",
    "a": "Adverb",
    "b": "Adjective",
    "c": "Verb",
    "d": "Pronoun",
    "correct_answer": "b"
  },
  {
    "question": "Which of the following is a descriptive adjective?",
    "a": "One",
    "b": "This",
    "c": "My",
    "d": "Beautiful",
    "correct_answer": "d"
  },
  {
    "question": "What type of adjective is \'Nigerian\' in \'Nigerian food\'?",
    "a": "Descriptive Adjective",
    "b": "Quantitative Adjective",
    "c": "Proper Adjective",
    "d": "Possessive Adjective",
    "correct_answer": "c"
  },
  {
    "question": "Which part of speech modifies a verb, adjective, or another adverb?",
    "a": "Noun",
    "b": "Adverb",
    "c": "Adjective",
    "d": "Preposition",
    "correct_answer": "b"
  },
  {
    "question": "Which of these adverbs indicates \'when\' something happens?",
    "a": "Quickly",
    "b": "Here",
    "c": "Tomorrow",
    "d": "Always",
    "correct_answer": "c"
  },
  {
    "question": "What part of speech shows the relationship between a noun or pronoun and other words?",
    "a": "Conjunction",
    "b": "Interjection",
    "c": "Preposition",
    "d": "Adverb",
    "correct_answer": "c"
  },
  {
    "question": "Which of the following is a preposition?",
    "a": "And",
    "b": "But",
    "c": "In",
    "d": "Wow",
    "correct_answer": "c"
  },
  {
    "question": "Which part of speech connects words, phrases, or clauses?",
    "a": "Preposition",
    "b": "Conjunction",
    "c": "Interjection",
    "d": "Adverb",
    "correct_answer": "b"
  },
  {
    "question": "Which of the following is a coordinating conjunction?",
    "a": "Because",
    "b": "Although",
    "c": "But",
    "d": "Since",
    "correct_answer": "c"
  },
  {
    "question": "What part of speech expresses strong emotion or sudden feeling?",
    "a": "Noun",
    "b": "Verb",
    "c": "Adjective",
    "d": "Interjection",
    "correct_answer": "d"
  }
]
```';

    // $practiceQuestions = str_replace('```html', '', $resQuestions);
    $practiceQuestions = str_replace('```json', '', $resQuestions);
    $practiceQuestions = str_replace('```', '', $practiceQuestions);

    $practiceQuestions = json_decode($practiceQuestions, true);
    // info($practiceQuestions);
    
    /*
    $practiceQuestions = '
      <ol>
        <li>What is Home Economics?</li>
        <li>List three importance of studying Home Economics.</li>
        <li>Define personal hygiene.</li>
        <li>Mention five practices of personal hygiene.</li>
        <li>Why is it important to keep our bodies clean?</li>
        <li>Give two examples of diseases that can be prevented by good personal hygiene.</li>
        <li>What is the meaning of environmental hygiene?</li>
        <li>List four activities that promote environmental hygiene.</li>
        <li>Explain why environmental hygiene is important for our health.</li>
        <li>How does a dirty environment affect our health?</li>
        <li>What are communicable diseases?</li>
        <li>Give three examples of communicable diseases.</li>
        <li>How can communicable diseases be spread?</li>
        <li>List two ways to prevent the spread of communicable diseases.</li>
        <li>What is meant by the term "food spoilage"?</li>
        <li>Name three causes of food spoilage.</li>
        <li>Mention two signs that food has spoiled.</li>
        <li>How can we prevent food from spoiling? (Give two methods).</li>
        <li>What is the importance of keeping food clean and safe to eat?</li>
        <li>Differentiate between personal hygiene and environmental hygiene.</li>
      </ol>
    ';
    */
    return $this->ok(['practice_questions' => $practiceQuestions]);
  }
}
