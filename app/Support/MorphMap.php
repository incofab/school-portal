<?php

namespace App\Support;

use App\Models as Models;

class MorphMap
{
  static function key($value): string|null
  {
    return array_search($value, self::MAP);
  }

  static function keys(array $values): array
  {
    $keys = [];
    foreach ($values as $key => $value) {
      if ($searchKey = array_search($value, self::MAP)) {
        $keys[] = $searchKey;
      }
    }
    return $keys;
  }

  static function value($key): string|null
  {
    return self::MAP[$key] ?? null;
  }

  const MAP = [
    'academic-session' => Models\AcademicSession::class,
    'admin' => Models\Admin::class,
    'admission-application' => Models\AdmissionApplication::class,
    'admission-form' => Models\AdmissionForm::class,
    'admission-form-purchase' => Models\AdmissionFormPurchase::class,
    'application-guardian' => Models\ApplicationGuardian::class,
    'assessment' => Models\Assessment::class,
    'assignment' => Models\Assignment::class,
    'assignment-submission' => Models\AssignmentSubmission::class,
    'association' => Models\Association::class,
    'attendance' => Models\Attendance::class,
    'classification' => Models\Classification::class,
    'classification-group' => Models\ClassificationGroup::class,
    'class-result-info' => Models\ClassResultInfo::class,
    'course' => Models\Course::class,
    'course-result' => Models\CourseResult::class,
    'course-result-info' => Models\CourseResultInfo::class,
    'course-session' => Models\CourseSession::class,
    'course-teacher' => Models\CourseTeacher::class,
    'event' => Models\Event::class,
    'event-courseable' => Models\EventCourseable::class,
    'exam' => Models\Exam::class,
    'exam-courseable' => Models\ExamCourseable::class,
    'fee' => Models\Fee::class,
    'fee-payment' => Models\FeePayment::class,
    'funding' => Models\Funding::class,
    'guardian-student' => Models\GuardianStudent::class,
    'institution' => Models\Institution::class,
    'institution-group' => Models\InstitutionGroup::class,
    'institution-setting' => Models\InstitutionSetting::class,
    'institution-user' => Models\InstitutionUser::class,
    'instruction' => Models\Instruction::class,
    'learning-evaluation' => Models\LearningEvaluation::class,
    'learning-evaluation-domain' => Models\LearningEvaluationDomain::class,
    'message' => Models\Message::class,
    'message-recipient' => Models\MessageRecipient::class,
    'passage' => Models\Passage::class,
    'payment-reference' => Models\PaymentReference::class,
    'pin' => Models\Pin::class,
    'pin-generator' => Models\PinGenerator::class,
    'question' => Models\Question::class,
    'receipt' => Models\Receipt::class,
    'registration-request' => Models\RegistrationRequest::class,
    'result-comment-template' => Models\ResultCommentTemplate::class,
    'result-publication' => Models\ResultPublication::class,
    'school-activity' => Models\SchoolActivity::class,
    'school-notification' => Models\SchoolNotification::class,
    'session-result' => Models\SessionResult::class,
    'student' => Models\Student::class,
    'student-class-movement' => Models\StudentClassMovement::class,
    'summary' => Models\Summary::class,
    'term-result' => Models\TermResult::class,
    'token-user' => Models\TokenUser::class,
    'topic' => Models\Topic::class,
    'transaction' => Models\Transaction::class,
    'user' => Models\User::class,
    'user-association' => Models\UserAssociation::class
  ];
}
