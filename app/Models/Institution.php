<?php

namespace App\Models;

use App\Enums\S3Folder;
use App\Support\Queries\InstitutionQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  public $casts = ['institution_group_id' => 'integer', 'user_id' => 'integer'];

  public static function generalRule($prefix = '')
  {
    return [
      $prefix . 'name' => ['required', 'string'],
      $prefix . 'institution_group_id' => [
        'required',
        'exists:institution_groups,id'
      ],
      $prefix . 'phone' => ['nullable', 'string'],
      $prefix . 'email' => ['nullable', 'string'],
      $prefix . 'address' => ['nullable', 'string']
    ];
  }

  public static function query(): InstitutionQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new InstitutionQueryBuilder($query);
  }

  public function getRouteKeyName()
  {
    return 'uuid';
  }

  public function resolveRouteBinding($value, $field = null)
  {
    $user = currentUser();
    $institutionModel = Institution::query()
      ->select('institutions.*')
      ->join(
        'institution_users',
        'institution_users.institution_id',
        'institutions.id'
      )
      ->where('uuid', $value)
      ->when(
        $user && !$user->isManager(),
        fn($q) => $q
          ->where('institution_users.user_id', $user->id)
          ->with(
            'institutionUsers',
            fn($q) => $q
              ->where('institution_users.user_id', $user->id)
              ->with('student')
          )
      )
      ->with('institutionSettings')
      ->first();

    abort_unless($institutionModel, 403, 'Institution not found for this user');

    return $institutionModel;
  }

  /** Return the institution folder without a leading or preceding slash */
  function folder(S3Folder $s3Folder = S3Folder::Base, $append = '')
  {
    $dir = "institutions/{$this->id}/{$s3Folder->value}";
    return $append ? "$dir/$append" : $dir;
  }

  static function generateInstitutionCode()
  {
    $key = mt_rand(100000, 999999);

    while (Institution::whereCode($key)->first()) {
      $key = mt_rand(100000, 999999);
    }

    return $key;
  }

  function courses()
  {
    return $this->hasMany(Course::class);
  }

  function classifications()
  {
    return $this->hasMany(Classification::class);
  }

  function classificationGroups()
  {
    return $this->hasMany(ClassificationGroup::class);
  }

  function users()
  {
    return $this->belongsToMany(User::class);
  }

  function institutionUsers()
  {
    return $this->hasMany(InstitutionUser::class);
  }

  function createdBy()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }

  function sessionResults()
  {
    return $this->hasMany(SessionResult::class);
  }

  function pins()
  {
    return $this->hasMany(Pin::class);
  }

  function pinGenerators()
  {
    return $this->hasMany(PinGenerator::class);
  }

  function fees()
  {
    return $this->hasMany(Fee::class);
  }

  function receiptTypes()
  {
    return $this->hasMany(ReceiptType::class);
  }

  function receipts()
  {
    return $this->hasMany(Receipt::class);
  }

  function feePayments()
  {
    return $this->hasMany(FeePayment::class);
  }

  function institutionSettings()
  {
    return $this->hasMany(InstitutionSetting::class);
  }

  function admissionApplications()
  {
    return $this->hasMany(AdmissionApplication::class);
  }

  function assessments()
  {
    return $this->hasMany(Assessment::class);
  }

  function learningEvaluationDomains()
  {
    return $this->hasMany(LearningEvaluationDomain::class);
  }

  function learningEvaluations()
  {
    return $this->hasMany(LearningEvaluation::class);
  }

  function resultCommentTemplates()
  {
    return $this->hasMany(ResultCommentTemplate::class);
  }

  function events()
  {
    return $this->hasMany(Event::class);
  }

  function assignments()
  {
    return $this->hasMany(Assignment::class);
  }

  function exams()
  {
    return $this->hasMany(Exam::class);
  }

  function tokenUsers()
  {
    return $this->hasMany(TokenUser::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  public function schoolActivities()
  {
    return $this->hasMany(SchoolActivity::class);
  }

  public function schemeOfWorks()
  {
    return $this->hasMany(SchemeOfWork::class);
  }

  public function admissionForms()
  {
    return $this->hasMany(AdmissionForm::class);
  }

  public function students()
  {
    return $this->hasManyThrough(
      User::class,
      InstitutionUser::class,
      'institution_id', // Foreign key on InstitutionUser table
      'id', // Foreign key on User table
      'id', // Local key on Institution table
      'user_id' // Local key on InstitutionUser table
    )->whereHas('institutionUsers', function ($query) {
      $query->where('role', 'student');
    });
  }
}
