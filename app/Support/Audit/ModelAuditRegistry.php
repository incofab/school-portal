<?php

namespace App\Support\Audit;

use App\Enums\Audit\ActivityLogCategory;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormPurchase;
use App\Models\ApplicationGuardian;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\ClassDivision;
use App\Models\ClassGroupResultInfo;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\ClassResultInfo;
use App\Models\Commission;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\CourseSession;
use App\Models\CourseTeacher;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Funding;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\InstitutionUser;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\ManualPayment;
use App\Models\Media;
use App\Models\PaymentReference;
use App\Models\Pin;
use App\Models\PinGenerator;
use App\Models\Receipt;
use App\Models\ReservedAccount;
use App\Models\ResultCommentTemplate;
use App\Models\ResultPublication;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\TermResult;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserTransaction;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model;

class ModelAuditRegistry
{
  public const OMITTED = '[omitted]';

  public const IGNORED_ATTRIBUTES = [
    'created_at',
    'updated_at',
    'deleted_at',
    'email_verified_at',
    'remember_token'
  ];

  public const LARGE_ATTRIBUTES = [
    'answer',
    'answers',
    'body',
    'comment',
    'comments',
    'content',
    'description',
    'document',
    'html',
    'message',
    'note',
    'notes',
    'raw',
    'remark',
    'remarks',
    'text'
  ];

  public const MODELS = [
    Institution::class => ['category' => ActivityLogCategory::Institution],
    InstitutionGroup::class => [
      'category' => ActivityLogCategory::InstitutionGroup
    ],
    User::class => ['category' => ActivityLogCategory::User],
    InstitutionUser::class => ['category' => ActivityLogCategory::User],
    Student::class => ['category' => ActivityLogCategory::Student],
    GuardianStudent::class => ['category' => ActivityLogCategory::Guardian],
    Classification::class => [
      'category' => ActivityLogCategory::Classification
    ],
    ClassificationGroup::class => [
      'category' => ActivityLogCategory::Classification
    ],
    ClassDivision::class => ['category' => ActivityLogCategory::Classification],
    Course::class => ['category' => ActivityLogCategory::Course],
    CourseTeacher::class => ['category' => ActivityLogCategory::Course],
    CourseSession::class => ['category' => ActivityLogCategory::Course],
    Topic::class => ['category' => ActivityLogCategory::Curriculum],
    LessonPlan::class => ['category' => ActivityLogCategory::Curriculum],
    LessonNote::class => ['category' => ActivityLogCategory::Curriculum],
    Assignment::class => ['category' => ActivityLogCategory::Assignment],
    AssignmentSubmission::class => [
      'category' => ActivityLogCategory::Assignment
    ],
    Attendance::class => ['category' => ActivityLogCategory::Attendance],
    Assessment::class => ['category' => ActivityLogCategory::Assessment],
    CourseResult::class => ['category' => ActivityLogCategory::Result],
    CourseResultInfo::class => ['category' => ActivityLogCategory::Result],
    ClassResultInfo::class => ['category' => ActivityLogCategory::Result],
    ClassGroupResultInfo::class => ['category' => ActivityLogCategory::Result],
    TermResult::class => ['category' => ActivityLogCategory::Result],
    SessionResult::class => ['category' => ActivityLogCategory::Result],
    ResultPublication::class => ['category' => ActivityLogCategory::Result],
    TermDetail::class => ['category' => ActivityLogCategory::Result],
    ResultCommentTemplate::class => ['category' => ActivityLogCategory::Result],
    PinGenerator::class => ['category' => ActivityLogCategory::Result],
    Pin::class => ['category' => ActivityLogCategory::Result],
    Fee::class => ['category' => ActivityLogCategory::Fee],
    FeePayment::class => ['category' => ActivityLogCategory::Payment],
    ManualPayment::class => ['category' => ActivityLogCategory::Payment],
    Receipt::class => ['category' => ActivityLogCategory::Payment],
    PaymentReference::class => ['category' => ActivityLogCategory::Payment],
    Funding::class => ['category' => ActivityLogCategory::Wallet],
    Transaction::class => ['category' => ActivityLogCategory::Wallet],
    UserTransaction::class => ['category' => ActivityLogCategory::Wallet],
    Withdrawal::class => ['category' => ActivityLogCategory::Wallet],
    BankAccount::class => ['category' => ActivityLogCategory::Wallet],
    ReservedAccount::class => ['category' => ActivityLogCategory::Wallet],
    Commission::class => ['category' => ActivityLogCategory::Wallet],
    AdmissionForm::class => ['category' => ActivityLogCategory::Admission],
    AdmissionApplication::class => [
      'category' => ActivityLogCategory::Admission
    ],
    AdmissionFormPurchase::class => [
      'category' => ActivityLogCategory::Admission
    ],
    ApplicationGuardian::class => [
      'category' => ActivityLogCategory::Admission
    ],
    Media::class => ['category' => ActivityLogCategory::Media]
  ];

  public static function models(): array
  {
    return array_keys(self::MODELS);
  }

  public static function category(Model $model): string
  {
    $category =
      self::MODELS[$model::class]['category'] ?? ActivityLogCategory::System;

    return $category instanceof ActivityLogCategory
      ? $category->value
      : $category;
  }

  public static function shouldAudit(Model $model): bool
  {
    return array_key_exists($model::class, self::MODELS);
  }

  public static function filterValues(array $values): array
  {
    return collect($values)
      ->reject(
        fn($value, string $key) => in_array(
          $key,
          self::IGNORED_ATTRIBUTES,
          true
        )
      )
      ->map(fn($value, string $key) => self::normalizeValue($key, $value))
      ->all();
  }

  private static function normalizeValue(string $key, mixed $value): mixed
  {
    if (in_array($key, self::LARGE_ATTRIBUTES, true)) {
      return self::OMITTED;
    }

    if ($value instanceof \BackedEnum) {
      return $value->value;
    }

    if ($value instanceof \UnitEnum) {
      return $value->name;
    }

    if ($value instanceof \DateTimeInterface) {
      return $value->format(DATE_ATOM);
    }

    if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
      $value = $value->toArray();
    }

    if (is_array($value)) {
      return collect($value)
        ->map(
          fn($item, $itemKey) => self::normalizeValue((string) $itemKey, $item)
        )
        ->all();
    }

    return $value;
  }
}
