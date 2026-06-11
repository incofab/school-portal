<?php

namespace App\Actions\CoursePractice;

use App\Enums\Audit\ActivityLogCategory;
use App\Enums\NoteStatusType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\LessonNote;
use App\Models\Topic;
use App\Models\TopicPracticeAttempt;
use App\Models\TopicPracticeSummary;
use App\Support\Audit\AcademicActivityLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class GenerateTopicPracticeQuestions
{
  public function __construct(
    private Institution $institution,
    private InstitutionUser $institutionUser,
    private Topic $topic,
    private mixed $coursePayload = null
  ) {
  }

  public static function run(
    Institution $institution,
    InstitutionUser $institutionUser,
    Topic $topic,
    mixed $coursePayload = null
  ): array {
    return (new self(
      $institution,
      $institutionUser,
      $topic,
      $coursePayload
    ))->execute();
  }

  public function execute(): array
  {
    $lessonNotes = $this->getPublishedLessonNotes();
    $practiceQuestions = $this->generateQuestions($lessonNotes);
    [$attempt, $summary] = $this->recordStudentGeneration($practiceQuestions);

    $this->logGeneration($practiceQuestions, $attempt);

    return [
      'course' => $this->coursePayload ?: $this->topic->course,
      'topic' => $this->topic,
      'attemptId' => $attempt?->id,
      'practiceSummary' => $summary,
      'practiceQuestions' => $practiceQuestions
    ];
  }

  private function getPublishedLessonNotes()
  {
    $lessonNotes = LessonNote::query()
      ->where('topic_id', $this->topic->id)
      ->where('status', NoteStatusType::Published->value)
      ->get();

    if ($lessonNotes->isEmpty()) {
      throw new HttpResponseException(
        response()->json(
          ['message' => 'You have to set Lesson Notes for this topic first'],
          401
        )
      );
    }

    return $lessonNotes;
  }

  private function generateQuestions($lessonNotes): array
  {
    $className = $this->studentClassPromptSuffix();
    $prompt = "You are a class teacher $className in a Nigerian Basic Education School.
    Analyze the following Lesson Notes and generate 20 objective questions aimed at helping the student prepare for
    upcoming class assessment test. Each question should have 4 options (option_a, option_b, option_c, option_d)
    where only one option is the correct answer.
    Return the response as valid JSON array of objects, where each object contains the following keys: 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer'.
    The value of the 'answer' should indicate the correct option (a,b,c,d - NOT 'option_a', 'option_b', 'option_c', 'option_d').
    Do not include comments, side comments, stylings, meta tags, etc.
    The response should look like this:
    [
      {
        'question' => 'What is the capital of France?',
        'option_b' => 'Paris',
        'option_a' => 'London',
        'option_c' => 'Berlin',
        'option_d' => 'Madrid',
        'answer' => 'B'
      },
      {
        'question' => 'What is the currency of Japan?',
        'option_a' => 'Yen',
        'option_b' => 'Dollar',
        'option_c' => 'Euro',
        'option_d' => 'Pound',
        'answer' => 'A'
      }
      ...
    ]
    Here are the lesson Notes :: $lessonNotes";

    $aiRes = initPrism()
      ->withPrompt($prompt)
      ->asText();

    $decodedQuestions = json_decode(trimAiResponse($aiRes->text), true) ?? [];

    return collect($decodedQuestions)
      ->filter(fn($question) => is_array($question))
      ->values()
      ->all();
  }

  private function studentClassPromptSuffix(): string
  {
    if (!$this->institutionUser->isStudent()) {
      return '';
    }

    $this->institutionUser->loadMissing(
      'student.classification.classificationGroup'
    );

    $classTitle =
      $this->institutionUser->student?->classification?->classificationGroup
        ?->title;

    return $classTitle ? "of $classTitle" : '';
  }

  private function recordStudentGeneration(array $practiceQuestions): array
  {
    if (!$this->institutionUser->isStudent()) {
      return [null, null];
    }

    $student = $this->institutionUser->student;
    abort_unless($student, 403, 'Student profile not found.');

    return DB::transaction(function () use ($student, $practiceQuestions) {
      $summary = TopicPracticeSummary::query()
        ->where('student_id', $student->id)
        ->where('topic_id', $this->topic->id)
        ->lockForUpdate()
        ->first();

      if (!$summary) {
        $summary = TopicPracticeSummary::query()->create([
          'institution_id' => $this->institution->id,
          'student_id' => $student->id,
          'classification_id' => $student->classification_id,
          'course_id' => $this->topic->course_id,
          'topic_id' => $this->topic->id,
          'attempts_count' => 0
        ]);
      }

      $attemptNumber = $summary->attempts_count + 1;
      $attempt = TopicPracticeAttempt::query()->create([
        'topic_practice_summary_id' => $summary->id,
        'institution_id' => $this->institution->id,
        'student_id' => $student->id,
        'classification_id' => $student->classification_id,
        'course_id' => $this->topic->course_id,
        'topic_id' => $this->topic->id,
        'attempt_number' => $attemptNumber,
        'questions' => $practiceQuestions,
        'questions_count' => count($practiceQuestions)
      ]);

      $summary
        ->forceFill([
          'classification_id' => $student->classification_id,
          'course_id' => $this->topic->course_id,
          'attempts_count' => $attemptNumber,
          'latest_score' => 0,
          'latest_questions_count' => count($practiceQuestions),
          'latest_percentage' => 0,
          'last_generated_at' => now()
        ])
        ->save();

      return [$attempt, $summary->fresh()];
    });
  }

  private function logGeneration(
    array $practiceQuestions,
    ?TopicPracticeAttempt $attempt
  ): void {
    app(AcademicActivityLogger::class)->workflowEvent(
      $this->institution,
      'question_bank.generated',
      ActivityLogCategory::Course,
      'generated_question_bank',
      'Practice questions generated.',
      [
        'topic_ids' => [$this->topic->id],
        'topic_count' => 1,
        'generated_count' => count($practiceQuestions),
        'topic_practice_attempt_id' => $attempt?->id,
        'for_role' =>
          $this->institutionUser->role instanceof \BackedEnum
            ? $this->institutionUser->role->value
            : $this->institutionUser->role
      ]
    );
  }
}
