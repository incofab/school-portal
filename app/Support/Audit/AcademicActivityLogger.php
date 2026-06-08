<?php

namespace App\Support\Audit;

use App\Enums\Audit\ActivityLogCategory;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Classification;
use App\Models\CourseTeacher;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\StudentClassMovement;
use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Model;

class AcademicActivityLogger
{
  public function studentCodeChanged(
    Institution $institution,
    Student $student,
    ?string $oldCode,
    string $newCode
  ): void {
    $this->base(
      'student.code_changed',
      ActivityLogCategory::Student,
      'changed_code'
    )
      ->on($student)
      ->inInstitution($institution)
      ->description('Student code changed.')
      ->properties($this->studentMetadata($student))
      ->oldValues(['code' => $oldCode])
      ->newValues(['code' => $newCode])
      ->log();
  }

  public function studentClassChanged(
    StudentClassMovement $movement,
    string $event = 'student.class_changed',
    string $action = 'changed_class',
    ?array $extra = null
  ): void {
    $movement->loadMissing([
      'student.user',
      'sourceClass',
      'destinationClass',
      'academicSession',
      'institution'
    ]);

    $properties = [
      ...$this->studentMetadata($movement->student),
      ...$this->movementMetadata($movement),
      ...$extra ?? []
    ];

    $this->base($event, ActivityLogCategory::Student, $action)
      ->on($movement->student)
      ->inInstitution($movement->institution)
      ->description('Student class movement recorded.')
      ->properties($properties)
      ->oldValues(['classification_id' => $movement->source_classification_id])
      ->newValues([
        'classification_id' => $movement->destination_classification_id
      ])
      ->log();
  }

  public function studentMovementSummary(
    Institution $institution,
    string $event,
    string $action,
    string $description,
    array $properties,
    ?Model $subject = null
  ): void {
    $this->base($event, ActivityLogCategory::Student, $action)
      ->on($subject)
      ->inInstitution($institution)
      ->description($description)
      ->properties($this->withAcademicPeriod($properties))
      ->log();
  }

  public function studentBulkUploadStarted(
    Institution $institution,
    Classification $classification,
    string $fileName
  ): void {
    $this->base(
      'student.bulk_upload_started',
      ActivityLogCategory::Student,
      'started_bulk_upload'
    )
      ->on($classification)
      ->inInstitution($institution)
      ->description('Student bulk upload started.')
      ->properties(
        $this->withAcademicPeriod([
          'classification_id' => $classification->id,
          'classification_title' => $classification->title,
          'file_name' => $fileName
        ])
      )
      ->log();
  }

  public function studentBulkUploadCompleted(
    Institution $institution,
    Classification $classification,
    string $fileName,
    int $createdCount
  ): void {
    $this->base(
      'student.bulk_upload_completed',
      ActivityLogCategory::Student,
      'completed_bulk_upload'
    )
      ->on($classification)
      ->inInstitution($institution)
      ->description('Student bulk upload completed.')
      ->properties(
        $this->withAcademicPeriod([
          'classification_id' => $classification->id,
          'classification_title' => $classification->title,
          'file_name' => $fileName,
          'created_count' => $createdCount
        ])
      )
      ->log();
  }

  public function studentBulkUploadFailed(
    Institution $institution,
    Classification $classification,
    string $fileName,
    \Throwable $throwable
  ): void {
    $this->base(
      'student.bulk_upload_failed',
      ActivityLogCategory::Student,
      'failed_bulk_upload'
    )
      ->on($classification)
      ->inInstitution($institution)
      ->description('Student bulk upload failed.')
      ->properties(
        $this->withAcademicPeriod([
          'classification_id' => $classification->id,
          'classification_title' => $classification->title,
          'file_name' => $fileName,
          'error_type' => $throwable::class,
          'error_message' => str($throwable->getMessage())
            ->limit(240)
            ->toString()
        ])
      )
      ->log();
  }

  public function workflowEvent(
    Institution $institution,
    string $event,
    ActivityLogCategory $category,
    string $action,
    string $description,
    array $properties = [],
    ?Model $subject = null
  ): void {
    $this->base($event, $category, $action)
      ->on($subject)
      ->inInstitution($institution)
      ->description($description)
      ->properties($this->withAcademicPeriod($properties))
      ->log();
  }

  public function guardianRecorded(GuardianStudent $guardianStudent): void
  {
    $guardianStudent->loadMissing(
      'student.user',
      'guardian',
      'student.classification'
    );

    $this->guardianEvent(
      'guardian.recorded',
      'recorded',
      'Guardian recorded for student.',
      $guardianStudent
    );
  }

  public function guardianAssigned(GuardianStudent $guardianStudent): void
  {
    $guardianStudent->loadMissing(
      'student.user',
      'guardian',
      'student.classification'
    );

    $this->guardianEvent(
      'guardian.assigned',
      'assigned',
      'Guardian assigned to student.',
      $guardianStudent
    );
  }

  public function guardianDependentRemoved(
    GuardianStudent $guardianStudent
  ): void {
    $guardianStudent->loadMissing(
      'student.user',
      'guardian',
      'student.classification'
    );

    $this->guardianEvent(
      'guardian.dependent_removed',
      'removed_dependent',
      'Guardian dependent removed.',
      $guardianStudent
    );
  }

  public function courseTeacherAssigned(CourseTeacher $courseTeacher): void
  {
    $courseTeacher->loadMissing('course', 'classification', 'user');

    $this->base(
      'course.teacher_assigned',
      ActivityLogCategory::Course,
      'assigned_teacher'
    )
      ->on($courseTeacher->course)
      ->inInstitution(
        $courseTeacher->course?->institution ?? currentInstitution()
      )
      ->description('Course teacher assigned.')
      ->properties($this->courseTeacherMetadata($courseTeacher))
      ->log();
  }

  public function courseTeacherRemoved(CourseTeacher $courseTeacher): void
  {
    $courseTeacher->loadMissing('course', 'classification', 'user');

    $this->base(
      'course.teacher_removed',
      ActivityLogCategory::Course,
      'removed_teacher'
    )
      ->on($courseTeacher->course)
      ->inInstitution(
        $courseTeacher->course?->institution ?? currentInstitution()
      )
      ->description('Course teacher removed.')
      ->properties($this->courseTeacherMetadata($courseTeacher))
      ->log();
  }

  public function assignmentSubmitted(
    Institution $institution,
    AssignmentSubmission $submission
  ): void {
    $submission->loadMissing(
      'assignment.course',
      'assignment.classifications',
      'student.user'
    );

    $this->base(
      'assignment.submitted',
      ActivityLogCategory::Assignment,
      'submitted'
    )
      ->on($submission->assignment)
      ->inInstitution($institution)
      ->description('Assignment submitted.')
      ->properties($this->assignmentSubmissionMetadata($submission))
      ->log();
  }

  public function assignmentScored(
    Institution $institution,
    AssignmentSubmission $submission,
    mixed $oldScore,
    mixed $newScore
  ): void {
    $submission->loadMissing(
      'assignment.course',
      'assignment.classifications',
      'student.user'
    );

    $this->base('assignment.scored', ActivityLogCategory::Assignment, 'scored')
      ->on($submission->assignment)
      ->inInstitution($institution)
      ->description('Assignment submission scored.')
      ->properties($this->assignmentSubmissionMetadata($submission))
      ->oldValues(['score' => $oldScore])
      ->newValues(['score' => $newScore])
      ->log();
  }

  public function attendanceRecorded(Attendance $attendance): void
  {
    $attendance->loadMissing(
      'institution',
      'institutionUser.user',
      'institutionUser.student.classification',
      'staffUser.user'
    );

    $this->base(
      'attendance.recorded',
      ActivityLogCategory::Attendance,
      'recorded'
    )
      ->on($attendance)
      ->inInstitution($attendance->institution)
      ->description('Attendance recorded.')
      ->properties($this->attendanceMetadata($attendance))
      ->log();
  }

  public function attendanceUpdated(
    Attendance $attendance,
    array $oldValues
  ): void {
    $attendance->loadMissing(
      'institution',
      'institutionUser.user',
      'institutionUser.student.classification',
      'staffUser.user'
    );

    $this->base(
      'attendance.updated',
      ActivityLogCategory::Attendance,
      'updated'
    )
      ->on($attendance)
      ->inInstitution($attendance->institution)
      ->description('Attendance updated.')
      ->properties($this->attendanceMetadata($attendance))
      ->oldValues($oldValues)
      ->newValues(
        collect($oldValues)
          ->mapWithKeys(
            fn($value, $key) => [$key => $attendance->getAttribute($key)]
          )
          ->all()
      )
      ->log();
  }

  public function attendanceBulkUpdated(
    Institution $institution,
    InstitutionUser $staffInstitutionUser,
    array $properties
  ): void {
    $staffInstitutionUser->loadMissing('user');

    $this->base(
      'attendance.bulk_updated',
      ActivityLogCategory::Attendance,
      'bulk_updated'
    )
      ->on($staffInstitutionUser)
      ->inInstitution($institution)
      ->description('Attendance batch updated.')
      ->properties($this->withAcademicPeriod($properties))
      ->log();
  }

  private function guardianEvent(
    string $event,
    string $action,
    string $description,
    GuardianStudent $guardianStudent
  ): void {
    $this->base($event, ActivityLogCategory::Guardian, $action)
      ->on($guardianStudent->student)
      ->inInstitution(
        $guardianStudent->student?->institutionUser?->institution ??
          currentInstitution()
      )
      ->description($description)
      ->properties([
        ...$this->studentMetadata($guardianStudent->student),
        'guardian_user_id' => $guardianStudent->guardian_user_id,
        'guardian_name' => $guardianStudent->guardian?->full_name,
        'guardian_email' => $guardianStudent->guardian?->email,
        'relationship' => $guardianStudent->relationship
      ])
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
      ->by(currentUser());
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

  private function movementMetadata(StudentClassMovement $movement): array
  {
    return $this->withAcademicPeriod([
      'student_class_movement_id' => $movement->id,
      'batch_no' => $movement->batch_no,
      'source_classification_id' => $movement->source_classification_id,
      'source_classification_title' => $movement->sourceClass?->title,
      'destination_classification_id' =>
        $movement->destination_classification_id,
      'destination_classification_title' => $movement->destinationClass?->title,
      'academic_session_id' => $movement->academic_session_id,
      'academic_session_title' => $movement->academicSession?->title,
      'term' => $movement->term,
      'revert_reference_id' => $movement->revert_reference_id
    ]);
  }

  private function courseTeacherMetadata(CourseTeacher $courseTeacher): array
  {
    return [
      'course_teacher_id' => $courseTeacher->id,
      'course_id' => $courseTeacher->course_id,
      'course_title' => $courseTeacher->course?->title,
      'course_code' => $courseTeacher->course?->code,
      'teacher_user_id' => $courseTeacher->user_id,
      'teacher_name' => $courseTeacher->user?->full_name,
      'teacher_email' => $courseTeacher->user?->email,
      'classification_id' => $courseTeacher->classification_id,
      'classification_title' => $courseTeacher->classification?->title
    ];
  }

  private function assignmentSubmissionMetadata(
    AssignmentSubmission $submission
  ): array {
    $assignment = $submission->assignment;

    return $this->withAcademicPeriod([
      ...$this->studentMetadata($submission->student),
      'assignment_id' => $submission->assignment_id,
      'assignment_submission_id' => $submission->id,
      'course_id' => $assignment?->course_id,
      'course_title' => $assignment?->course?->title,
      'course_code' => $assignment?->course?->code,
      'classification_ids' => $assignment?->classifications
        ?->pluck('id')
        ->all(),
      'classification_titles' => $assignment?->classifications
        ?->pluck('title')
        ->all(),
      'max_score' => $assignment?->max_score,
      'academic_session_id' => $assignment?->academic_session_id,
      'term' =>
        $assignment?->term instanceof \BackedEnum
          ? $assignment->term->value
          : $assignment?->term
    ]);
  }

  private function attendanceMetadata(Attendance $attendance): array
  {
    $institutionUser = $attendance->institutionUser;
    $student = $institutionUser?->student;

    return $this->withAcademicPeriod([
      'attendance_id' => $attendance->id,
      'institution_user_id' => $attendance->institution_user_id,
      'attendee_user_id' => $institutionUser?->user_id,
      'attendee_name' => $institutionUser?->user?->full_name,
      'attendee_role' =>
        $institutionUser?->role instanceof \BackedEnum
          ? $institutionUser->role->value
          : $institutionUser?->role,
      'student_id' => $student?->id,
      'student_code' => $student?->code,
      'classification_id' => $student?->classification_id,
      'classification_title' => $student?->classification?->title,
      'staff_institution_user_id' => $attendance->institution_staff_user_id,
      'staff_user_id' => $attendance->staffUser?->user_id,
      'staff_name' => $attendance->staffUser?->user?->full_name,
      'signed_in_at' => $attendance->signed_in_at?->toISOString(),
      'signed_out_at' => $attendance->signed_out_at?->toISOString()
    ]);
  }

  private function withAcademicPeriod(array $properties): array
  {
    $settings = SettingsHandler::makeFromRoute();

    return [
      ...$properties,
      'current_academic_session_id' => $settings->getCurrentAcademicSession(),
      'current_term' => $settings->getCurrentTerm()
    ];
  }
}
