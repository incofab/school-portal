<?php

namespace App\Models;

use App\Support\Queries\InstitutionQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
  use HasFactory, SoftDeletes;

  protected $guarded = [];
  public static function generalRule($prefix = '')
  {
    return [
      $prefix . 'name' => ['required', 'string'],
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
        $user && !$user->isManagerAdmin(),
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

  function pinPrints()
  {
    return $this->hasMany(PinPrint::class);
  }

  function fees()
  {
    return $this->hasMany(Fee::class);
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

  function events()
  {
    return $this->hasMany(Event::class);
  }

  function exams()
  {
    return $this->hasMany(Exam::class);
  }
}
