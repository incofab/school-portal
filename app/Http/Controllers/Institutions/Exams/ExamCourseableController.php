<?php

namespace App\Http\Controllers\Institutions\Exams;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Question;
use App\Models\Student;
use App\Models\TheoryQuestion;
use App\Rules\ValidateMorphRule;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExamCourseableController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Institution $institution, Exam $exam)
  {
    $exam->load([
      'examable' => function (MorphTo $morphTo) {
        $morphTo->morphWith([Student::class => ['user']]);
      }
    ]);
    $query = $exam
      ->examCourseables()
      ->getQuery()
      ->with('courseable', function (MorphTo $morphTo) {
        $morphTo->morphWith([CourseSession::class => ['course']]);
      });

    return Inertia::render('institutions/exams/list-exam-courseables', [
      'examCourseables' => paginateFromRequest($query->latest('id')),
      'exam' => $exam->load('event')
    ]);
  }

  public function show(
    Request $request,
    Institution $institution,
    Exam $exam,
    ExamCourseable $examCourseable
  ) {
    $this->ensureCanEvaluateTheory($exam, $examCourseable);
    $examCourseable->load([
      'exam.examable' => function (MorphTo $morphTo) {
        $morphTo->morphWith([Student::class => ['user']]);
      },
      'exam.event',
      'courseable' => function (MorphTo $morphTo) {
        $morphTo->morphWith([
          CourseSession::class => [
            'course',
            'questions',
            'theoryQuestions',
            'passages',
            'instructions'
          ]
        ]);
      }
    ]);

    return Inertia::render('institutions/exams/show-exam-courseables', [
      'examCourseable' => $examCourseable
    ]);
  }

  public function create(Institution $institution, Exam $exam)
  {
    return Inertia::render('institutions/exams/create-edit-exam-courseables', [
      'exam' => $exam,
      'exam_courseables' => $exam
        ->examCourseables()
        ->with('courseable')
        ->get()
    ]);
  }

  public function store(Request $request, Institution $institution, Exam $exam)
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

    foreach ($data['courseables'] as $courseable) {
      $questionsCount = Question::query()
        ->where(
          collect($courseable)
            ->only(['courseable_id', 'courseable_type'])
            ->toArray()
        )
        ->count();
      $theoryQuestionQuery = TheoryQuestion::query()->where(
        collect($courseable)
          ->only(['courseable_id', 'courseable_type'])
          ->toArray()
      );
      $theoryNumOfQuestions = (clone $theoryQuestionQuery)->count();
      $exam->examCourseables()->updateOrCreate($courseable, [
        'num_of_questions' => $questionsCount,
        'theory_score' => 0,
        'theory_max_score' => (clone $theoryQuestionQuery)->sum('marks'),
        'theory_num_of_questions' => $theoryNumOfQuestions,
        'theory_question_scores' => null,
        'theory_evaluated' => $theoryNumOfQuestions === 0
      ]);
    }

    return $this->ok();
  }

  public function evaluateTheory(
    Request $request,
    Institution $institution,
    Exam $exam,
    ExamCourseable $examCourseable
  ) {
    $this->ensureCanEvaluateTheory($exam, $examCourseable);

    $examCourseable->load([
      'courseable' => function (MorphTo $morphTo) {
        $morphTo->morphWith([
          CourseSession::class => ['course', 'theoryQuestions']
        ]);
      }
    ]);

    $theoryQuestions = $examCourseable->courseable->theoryQuestions()->get();
    $data = $request->validate([
      'scores' => ['required', 'array']
    ]);
    $scores = [];

    foreach ($theoryQuestions as $question) {
      $rawScore = data_get($data['scores'], (string) $question->id);
      if ($rawScore === null || $rawScore === '') {
        throw ValidationException::withMessages([
          "scores.{$question->id}" => 'Enter a score for this theory question.'
        ]);
      }

      if (
        !is_numeric($rawScore) ||
        $rawScore < 0 ||
        $rawScore > $question->marks
      ) {
        throw ValidationException::withMessages([
          "scores.{$question->id}" => "Score must be between 0 and {$question->marks}."
        ]);
      }

      $scores[$question->id] = (float) $rawScore;
    }

    DB::transaction(function () use (
      $exam,
      $examCourseable,
      $theoryQuestions,
      $scores
    ) {
      $theoryScore = array_sum($scores);
      $examCourseable
        ->fill([
          'theory_score' => $theoryScore,
          'theory_max_score' => $theoryQuestions->sum('marks'),
          'theory_num_of_questions' => $theoryQuestions->count(),
          'theory_question_scores' => $scores,
          'theory_evaluated' => true
        ])
        ->save();

      $exam->load('examCourseables');
      $examCourseables = $exam->examCourseables;
      $hasUnevaluatedTheory = $examCourseables->contains(
        fn(ExamCourseable $courseable) => $courseable->theory_num_of_questions >
          0 && !$courseable->theory_evaluated
      );

      $exam
        ->fill([
          'theory_score' => $examCourseables->sum('theory_score'),
          'theory_max_score' => $examCourseables->sum('theory_max_score'),
          'theory_evaluated' => !$hasUnevaluatedTheory
        ])
        ->save();
    });

    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    ExamCourseable $examCourseable
  ) {
    $examCourseable->delete();

    return $this->ok();
  }

  private function ensureCanEvaluateTheory(
    Exam $exam,
    ExamCourseable $examCourseable
  ): void {
    abort_unless($examCourseable->exam_id === $exam->id, 404);
    abort_unless($exam->isended(), 403, 'Exam has not ended');

    if (currentInstitutionUser()->isAdmin()) {
      return;
    }

    abort_unless(
      currentInstitutionUser()->role === InstitutionUserType::Teacher,
      403,
      'Only teachers and admins can evaluate theory answers'
    );
    // return;

    $exam->loadMissing('event.classification', 'examable');
    if ($exam->examable instanceof Student) {
      $exam->examable->loadMissing('classification');
    }
    $examCourseable->loadMissing('courseable.course');
    $user = currentUser();
    $classification =
      $exam->event?->classification ??
      ($exam->examable instanceof Student
        ? $exam->examable->classification
        : null);
    $classificationId = $classification?->id;
    $isFormTeacher = $classification?->form_teacher_id === $user->id;

    if ($isFormTeacher) {
      return;
    }

    $isCourseTeacher = false;
    if ($classificationId && $examCourseable->courseable?->course_id) {
      $isCourseTeacher = CourseTeacher::query()
        ->where('institution_id', currentInstitution()->id)
        ->where('classification_id', $classificationId)
        ->where('course_id', $examCourseable->courseable->course_id)
        ->where('user_id', $user->id)
        ->exists();
    }

    abort_unless(
      $isCourseTeacher,
      403,
      'You are not allowed to evaluate this theory paper'
    );
  }
}
