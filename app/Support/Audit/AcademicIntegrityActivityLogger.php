<?php

namespace App\Support\Audit;

use App\Enums\AdmissionStatusType;
use App\Enums\Audit\ActivityLogCategory;
use App\Enums\Audit\ActivityLogSeverity;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormPurchase;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\Exam;
use App\Models\ExamCourseable;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\PinGenerator;
use App\Models\ResultPublication;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AcademicIntegrityActivityLogger
{
  public function resultScoreRecorded(
    CourseResult $courseResult,
    ?CourseResult $previousResult = null
  ): void {
    $courseResult->loadMissing([
      'institution',
      'student.user',
      'classification',
      'course',
      'academicSession',
      'teacher'
    ]);

    $isUpdate = (bool) $previousResult;

    $logger = $this->base(
      $isUpdate ? 'result.score_updated' : 'result.score_recorded',
      ActivityLogCategory::Result,
      $isUpdate ? 'updated_score' : 'recorded_score'
    )
      ->severity(
        $isUpdate ? ActivityLogSeverity::Warning : ActivityLogSeverity::Notice
      )
      ->on($courseResult)
      ->inInstitution($courseResult->institution)
      ->description(
        $isUpdate ? 'Result score updated.' : 'Result score recorded.'
      )
      ->properties($this->courseResultMetadata($courseResult));

    if ($isUpdate) {
      $logger
        ->oldValues([
          'exam' => $previousResult?->exam,
          'result' => $previousResult?->result,
          'grade' => $previousResult?->grade,
          'assessment_values' => $previousResult?->assessment_values
            ? collect($previousResult->assessment_values)->all()
            : []
        ])
        ->newValues([
          'exam' => $courseResult->exam,
          'result' => $courseResult->result,
          'grade' => $courseResult->grade,
          'assessment_values' => $courseResult->assessment_values
            ? collect($courseResult->assessment_values)->all()
            : []
        ]);
    }

    $logger->log();
  }

  public function resultScoreDeleted(CourseResult $courseResult): void
  {
    $courseResult->loadMissing([
      'institution',
      'student.user',
      'classification',
      'course',
      'academicSession',
      'teacher'
    ]);

    $this->base(
      'result.score_deleted',
      ActivityLogCategory::Result,
      'deleted_score'
    )
      ->severity(ActivityLogSeverity::Warning)
      ->on($courseResult)
      ->inInstitution($courseResult->institution)
      ->description('Result score deleted.')
      ->properties($this->courseResultMetadata($courseResult))
      ->oldValues([
        'exam' => $courseResult->exam,
        'result' => $courseResult->result,
        'grade' => $courseResult->grade
      ])
      ->log();
  }

  public function resultProcessingStarted(
    Institution $institution,
    Classification $classification,
    array $metadata
  ): void {
    $this->workflow(
      $institution,
      'result.processing_started',
      ActivityLogCategory::Result,
      'started_processing',
      'Result processing started.',
      [
        ...$this->classificationMetadata($classification),
        ...$this->periodMetadata($metadata)
      ],
      $classification,
      ActivityLogSeverity::Notice
    );
  }

  public function resultProcessingCompleted(
    ClassResultInfo $classResultInfo
  ): void {
    $classResultInfo->loadMissing('classification', 'academicSession');

    $this->workflow(
      $classResultInfo->classification?->institution ?? currentInstitution(),
      'result.processing_completed',
      ActivityLogCategory::Result,
      'completed_processing',
      'Result processing completed.',
      [
        ...$this->classResultInfoMetadata($classResultInfo),
        'num_of_students' => $classResultInfo->num_of_students,
        'num_of_courses' => $classResultInfo->num_of_courses,
        'average' => $classResultInfo->average
      ],
      $classResultInfo,
      ActivityLogSeverity::Notice
    );
  }

  public function resultProcessingFailed(
    Institution $institution,
    Classification $classification,
    array $metadata,
    \Throwable $throwable
  ): void {
    $this->workflow(
      $institution,
      'result.processing_failed',
      ActivityLogCategory::Result,
      'failed_processing',
      'Result processing failed.',
      [
        ...$this->classificationMetadata($classification),
        ...$this->periodMetadata($metadata),
        'error_type' => $throwable::class,
        'error_message' => str($throwable->getMessage())
          ->limit(240)
          ->toString()
      ],
      $classification,
      ActivityLogSeverity::Critical
    );
  }

  public function resultLockChanged(
    ClassResultInfo $classResultInfo,
    bool $oldValue,
    bool $newValue
  ): void {
    $classResultInfo->loadMissing('classification', 'academicSession');
    $event = $newValue ? 'result.locked' : 'result.unlocked';

    $this->base(
      $event,
      ActivityLogCategory::Result,
      $newValue ? 'locked' : 'unlocked'
    )
      ->severity(
        $newValue ? ActivityLogSeverity::Notice : ActivityLogSeverity::Critical
      )
      ->on($classResultInfo)
      ->inInstitution($classResultInfo->classification?->institution)
      ->description($newValue ? 'Result locked.' : 'Result unlocked.')
      ->properties($this->classResultInfoMetadata($classResultInfo))
      ->oldValues(['is_locked' => $oldValue])
      ->newValues(['is_locked' => $newValue])
      ->log();
  }

  public function resultPublished(
    Institution $institution,
    ResultPublication $publication,
    int $resultCount,
    array $classificationIds,
    bool $sendToGuardiansWhatsapp
  ): void {
    $publication->loadMissing('academicSession');

    $this->base('result.published', ActivityLogCategory::Result, 'published')
      ->severity(ActivityLogSeverity::Critical)
      ->on($publication)
      ->inInstitution($institution)
      ->description('Result published.')
      ->properties([
        'result_publication_id' => $publication->id,
        'academic_session_id' => $publication->academic_session_id,
        'academic_session_title' => $publication->academicSession?->title,
        'term' => $this->enumValue($publication->term),
        'published_result_count' => $resultCount,
        'num_of_results' => $publication->num_of_results,
        'num_of_students' => $publication->num_of_students,
        'classification_ids' => array_values($classificationIds),
        'send_to_guardians_whatsapp' => $sendToGuardiansWhatsapp,
        'payment_structure' => $this->enumValue($publication->payment_structure)
      ])
      ->log();
  }

  public function resultPinGenerated(
    Institution $institution,
    PinGenerator $pinGenerator
  ): void {
    $this->base(
      'result_pin.generated',
      ActivityLogCategory::Result,
      'generated_pin'
    )
      ->severity(ActivityLogSeverity::Notice)
      ->on($pinGenerator)
      ->inInstitution($institution)
      ->description('Result PINs generated.')
      ->properties([
        'pin_generator_id' => $pinGenerator->id,
        'reference' => $pinGenerator->reference,
        'num_of_pins' => $pinGenerator->num_of_pins,
        'comment' => $pinGenerator->comment
      ])
      ->log();
  }

  public function resultPinUsed(
    Institution $institution,
    Pin $pin,
    TermResult $termResult,
    Student $student
  ): void {
    $termResult->loadMissing('classification', 'academicSession');
    $student->loadMissing('user', 'classification');

    $this->base('result_pin.used', ActivityLogCategory::Result, 'used_pin')
      ->severity(ActivityLogSeverity::Security)
      ->on($termResult)
      ->inInstitution($institution)
      ->description('Result PIN used to activate a result.')
      ->properties([
        'pin_id' => $pin->id,
        'pin_generator_id' => $pin->pin_generator_id,
        ...$this->termResultMetadata($termResult),
        ...$this->studentMetadata($student)
      ])
      ->log();
  }

  // public function examCreated(Exam $exam, bool $wasExisting = false): void
  // {
  //     if ($wasExisting) {
  //         return;
  //     }

  //     $exam->loadMissing('institution', 'event.classification', 'examable');

  //     $this->base('exam.created', ActivityLogCategory::Exam, 'created')
  //         ->severity(ActivityLogSeverity::Notice)
  //         ->on($exam)
  //         ->inInstitution($exam->institution)
  //         ->description('Exam created.')
  //         ->properties($this->examMetadata($exam))
  //         ->log();
  // }

  public function examStarted(Exam $exam): void
  {
    $exam->loadMissing('institution', 'event.classification', 'examable');

    $this->base('exam.started', ActivityLogCategory::Exam, 'started')
      ->severity(ActivityLogSeverity::Notice)
      ->on($exam)
      ->inInstitution($exam->institution)
      ->description('Exam started.')
      ->properties($this->examMetadata($exam))
      ->log();
  }

  public function examSubmitted(Exam $exam, bool $reEvaluated = false): void
  {
    $exam->loadMissing('institution', 'event.classification', 'examable');

    $this->base(
      $reEvaluated ? 'exam.rescored' : 'exam.submitted',
      ActivityLogCategory::Exam,
      $reEvaluated ? 'rescored' : 'submitted'
    )
      ->severity(ActivityLogSeverity::Notice)
      ->on($exam)
      ->inInstitution($exam->institution)
      ->description(
        $reEvaluated ? 'Exam rescored.' : 'Exam submitted and scored.'
      )
      ->properties([
        ...$this->examMetadata($exam),
        'score' => $exam->score,
        'theory_score' => $exam->theory_score,
        'theory_max_score' => $exam->theory_max_score,
        'num_of_questions' => $exam->num_of_questions,
        'theory_evaluated' => $exam->theory_evaluated,
        're_evaluated' => $reEvaluated
      ])
      ->log();
  }

  public function examTheoryScored(
    Exam $exam,
    ExamCourseable $examCourseable,
    float|int $oldScore,
    float|int $newScore,
    int $questionCount
  ): void {
    $exam->loadMissing('institution', 'event.classification', 'examable');
    $examCourseable->loadMissing('courseable');

    $this->base(
      'exam.theory_scored',
      ActivityLogCategory::Exam,
      'scored_theory'
    )
      ->severity(ActivityLogSeverity::Notice)
      ->on($examCourseable)
      ->inInstitution($exam->institution)
      ->description('Exam theory answers scored.')
      ->properties([
        ...$this->examMetadata($exam),
        'exam_courseable_id' => $examCourseable->id,
        'courseable_id' => $examCourseable->courseable_id,
        'courseable_type' => $examCourseable->courseable_type,
        'question_count' => $questionCount,
        'theory_max_score' => $examCourseable->theory_max_score
      ])
      ->oldValues(['theory_score' => $oldScore])
      ->newValues(['theory_score' => $newScore])
      ->log();
  }

  public function admissionApplicationSubmitted(
    AdmissionApplication $application
  ): void {
    $application->loadMissing('institution', 'admissionForm.academicSession');

    $this->base(
      'admission.application_submitted',
      ActivityLogCategory::Admission,
      'submitted_application'
    )
      ->severity(ActivityLogSeverity::Notice)
      ->on($application)
      ->inInstitution($application->institution)
      ->description('Admission application submitted.')
      ->properties($this->admissionApplicationMetadata($application))
      ->log();
  }

  public function admissionFormPurchased(
    AdmissionFormPurchase $purchase,
    ?AdmissionApplication $application,
    AdmissionForm $admissionForm,
    array $metadata = []
  ): void {
    $admissionForm->loadMissing('institution', 'academicSession');
    $application?->loadMissing('admissionForm');

    $this->base(
      'admission.form_purchased',
      ActivityLogCategory::Admission,
      'purchased_form'
    )
      ->severity(ActivityLogSeverity::Notice)
      ->on($application ?? $admissionForm)
      ->inInstitution($admissionForm->institution)
      ->description('Admission form purchased.')
      ->properties([
        'admission_form_purchase_id' => $purchase->id,
        'admission_form_id' => $admissionForm->id,
        'admission_form_title' => $admissionForm->title,
        'academic_session_id' => $admissionForm->academic_session_id,
        'academic_session_title' => $admissionForm->academicSession?->title,
        'amount' => $metadata['amount'] ?? null,
        'merchant' => $metadata['merchant'] ?? null,
        'reference' => $metadata['reference'] ?? $purchase->reference,
        ...$application ? $this->admissionApplicationMetadata($application) : []
      ])
      ->log();
  }

  public function admissionApplicationStatusChanged(
    Institution $institution,
    AdmissionApplication $application,
    string|AdmissionStatusType|null $oldStatus,
    string|AdmissionStatusType $newStatus,
    ?Classification $classification = null
  ): void {
    $application->loadMissing('admissionForm.academicSession');

    $status = $this->enumValue($newStatus);
    $event = match ($status) {
      AdmissionStatusType::Admitted->value => 'admission.application_approved',
      AdmissionStatusType::Declined->value => 'admission.application_rejected',
      default => 'admission.application_reviewed'
    };

    $this->base($event, ActivityLogCategory::Admission, 'reviewed_application')
      ->severity(
        $status === AdmissionStatusType::Admitted->value
          ? ActivityLogSeverity::Critical
          : ActivityLogSeverity::Notice
      )
      ->on($application)
      ->inInstitution($institution)
      ->description('Admission application status changed.')
      ->properties([
        ...$this->admissionApplicationMetadata($application),
        ...$classification ? $this->classificationMetadata($classification) : []
      ])
      ->oldValues(['admission_status' => $this->enumValue($oldStatus)])
      ->newValues(['admission_status' => $status])
      ->log();
  }

  private function workflow(
    ?Institution $institution,
    string $event,
    ActivityLogCategory $category,
    string $action,
    string $description,
    array $properties,
    ?Model $subject,
    ActivityLogSeverity $severity
  ): void {
    $this->base($event, $category, $action)
      ->severity($severity)
      ->on($subject)
      ->inInstitution($institution)
      ->description($description)
      ->properties($properties)
      ->log();
  }

  private function base(
    string $event,
    ActivityLogCategory $category,
    string $action
  ): ActivityLogger {
    return app(ActivityLogger::class)
      ->event($event)
      ->category($category)
      ->action($action)
      ->by(Auth::user());
  }

  private function courseResultMetadata(CourseResult $courseResult): array
  {
    return [
      'course_result_id' => $courseResult->id,
      ...$this->studentMetadata($courseResult->student),
      ...$this->classificationMetadata($courseResult->classification),
      'course_id' => $courseResult->course_id,
      'course_title' => $courseResult->course?->title,
      'course_code' => $courseResult->course?->code,
      'teacher_user_id' => $courseResult->teacher_user_id,
      'teacher_name' => $courseResult->teacher?->full_name,
      ...$this->periodMetadata([
        'academic_session_id' => $courseResult->academic_session_id,
        'academic_session_title' => $courseResult->academicSession?->title,
        'term' => $courseResult->term,
        'for_mid_term' => $courseResult->for_mid_term
      ]),
      'exam' => $courseResult->exam,
      'result' => $courseResult->result,
      'grade' => $courseResult->grade
    ];
  }

  private function classResultInfoMetadata(
    ClassResultInfo $classResultInfo
  ): array {
    return [
      'class_result_info_id' => $classResultInfo->id,
      ...$this->classificationMetadata($classResultInfo->classification),
      ...$this->periodMetadata([
        'academic_session_id' => $classResultInfo->academic_session_id,
        'academic_session_title' => $classResultInfo->academicSession?->title,
        'term' => $classResultInfo->term,
        'for_mid_term' => $classResultInfo->for_mid_term
      ]),
      'is_locked' => $classResultInfo->is_locked
    ];
  }

  private function termResultMetadata(TermResult $termResult): array
  {
    return [
      'term_result_id' => $termResult->id,
      ...$this->classificationMetadata($termResult->classification),
      ...$this->periodMetadata([
        'academic_session_id' => $termResult->academic_session_id,
        'academic_session_title' => $termResult->academicSession?->title,
        'term' => $termResult->term,
        'for_mid_term' => $termResult->for_mid_term
      ]),
      'is_activated' => $termResult->is_activated,
      'result_publication_id' => $termResult->result_publication_id
    ];
  }

  private function examMetadata(Exam $exam): array
  {
    return [
      'exam_id' => $exam->id,
      'exam_no' => $exam->exam_no,
      'event_id' => $exam->event_id,
      'event_title' => $exam->event?->title,
      'event_type' => $this->enumValue($exam->event?->type),
      'status' => $this->enumValue($exam->status),
      ...$this->classificationMetadata($exam->event?->classification),
      ...$this->periodMetadata([
        'academic_session_id' => $exam->event?->academic_session_id,
        'term' => $exam->event?->term,
        'for_mid_term' => $exam->event?->for_mid_term
      ]),
      'examable_type' => $exam->examable_type,
      'examable_id' => $exam->examable_id,
      'examable_name' => method_exists($exam, 'getExamableName')
        ? $exam->getExamableName()
        : null
    ];
  }

  private function admissionApplicationMetadata(
    AdmissionApplication $application
  ): array {
    return [
      'admission_application_id' => $application->id,
      'application_no' => $application->application_no,
      'reference' => $application->reference,
      'applicant_name' => $application->name,
      'admission_status' => $this->enumValue($application->admission_status),
      'intended_class_of_admission' =>
        $application->intended_class_of_admission,
      'admission_form_id' => $application->admission_form_id,
      'admission_form_title' => $application->admissionForm?->title,
      'academic_session_id' =>
        $application->admissionForm?->academic_session_id,
      'academic_session_title' =>
        $application->admissionForm?->academicSession?->title,
      'guardian_count' => $application->relationLoaded('applicationGuardians')
        ? $application->applicationGuardians->count()
        : $application->applicationGuardians()->count()
    ];
  }

  private function studentMetadata(?Student $student): array
  {
    if (!$student) {
      return [];
    }

    $student->loadMissing('user', 'classification');

    return [
      'student_id' => $student->id,
      'student_code' => $student->code,
      'student_name' => $student->user?->full_name,
      'classification_id' => $student->classification_id,
      'classification_title' => $student->classification?->title
    ];
  }

  private function classificationMetadata(
    ?Classification $classification
  ): array {
    if (!$classification) {
      return [];
    }

    return [
      'classification_id' => $classification->id,
      'classification_title' => $classification->title
    ];
  }

  private function periodMetadata(array $metadata): array
  {
    return collect($metadata)
      ->map(fn($value) => $this->enumValue($value))
      ->all();
  }

  private function enumValue(mixed $value): mixed
  {
    return $value instanceof \BackedEnum ? $value->value : $value;
  }
}
