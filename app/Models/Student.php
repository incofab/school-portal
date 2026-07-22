<?php

namespace App\Models;

use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Support\Queries\StudentQueryBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends BaseModel
{
  use HasFactory, SoftDeletes;

  public $guarded = [];
  protected $casts = [
    'user_id' => 'integer',
    'classification_id' => 'integer'
  ];

  protected $appends = ['full_code'];

  public static function query(): StudentQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new StudentQueryBuilder($query);
  }

  function studentFees(
    string|TermType|null $term = null,
    int|AcademicSession|null $academicSessionId = null,
    bool $allFees = false
  ) {
    // info([
    //   'term' => $term,
    //   'academicSessionId' => $academicSessionId
    // ]);
    // dd('dlsd');
    $fees = $allFees
      ? Fee::all()
      : Fee::query()
        ->where(function ($query) use ($term, $academicSessionId) {
          $query
            ->where(function ($q) use ($term, $academicSessionId) {
              $q->when(
                $term && $academicSessionId,
                fn($qq) => $qq
                  ->where('payment_interval', PaymentInterval::Termly)
                  ->where('term', $term)
                  ->where('academic_session_id', $academicSessionId)
              );
            })
            ->orWhere(function ($q) use ($academicSessionId) {
              $q->when(
                $academicSessionId,
                fn($qq) => $qq
                  ->where('payment_interval', PaymentInterval::Sessional)
                  ->where('academic_session_id', $academicSessionId)
                  ->whereNull('term')
              );
            })
            ->orWhere(function ($q) {
              $q->where('payment_interval', PaymentInterval::OneTime)
                ->whereNull('term')
                ->whereNull('academic_session_id');
            });
        })
        ->get();

    $studentFees = [];
    /** @var Fee $fee */
    foreach ($fees as $key => $fee) {
      if ($fee->forStudent($this, $this->classification)) {
        $studentFees[] = $fee;
      }
    }
    return $studentFees;
  }

  static function generateStudentID()
  {
    $prefix = date('Y');

    $key = $prefix . rand(1000000, 9999999);

    while (Student::where('code', '=', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }

    return $key;
  }

  protected function fullCode(): Attribute
  {
    $initials = currentInstitution()?->initials;
    $prefix = $initials ? "$initials/" : '';
    return Attribute::make(get: fn() => "{$prefix}{$this->code}");
  }

  static function stripInitials(string $studentCode)
  {
    $pos = strpos($studentCode, '/') ?? 0;

    return substr($studentCode, $pos);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function course()
  {
    return $this->hasMany(Course::class);
  }

  function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function courseResults()
  {
    return $this->hasMany(CourseResult::class);
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }

  function sessionResults()
  {
    return $this->hasMany(SessionResult::class);
  }

  function classMovement()
  {
    return $this->hasMany(StudentClassMovement::class);
  }

  function guardian()
  {
    return $this->hasOneThrough(
      User::class,
      GuardianStudent::class,
      'student_id',
      'id',
      'id',
      'guardian_user_id'
    );
  }

  function assignmentSubmissions()
  {
    return $this->hasMany(AssignmentSubmission::class);
  }

  function topicPracticeSummaries()
  {
    return $this->hasMany(TopicPracticeSummary::class);
  }

  function topicPracticeAttempts()
  {
    return $this->hasMany(TopicPracticeAttempt::class);
  }
}
