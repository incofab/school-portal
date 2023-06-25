<?php

namespace App\Models;

use App\Support\Queries\InstitutionQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
  use HasFactory;

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
    if (!$user) {
      return null;
    }
    $institutionModel = Institution::query()
      ->select('institutions.*')
      ->join(
        'institution_users',
        'institution_users.institution_id',
        'institutions.id'
      )
      ->where('uuid', $value)
      ->where('institution_users.user_id', $user->id)
      ->with(
        'institutionUsers',
        fn($q) => $q
          ->where('institution_users.user_id', $user->id)
          ->with('student')
      )
      ->firstOrFail();
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
    return $this->belongsTo(User::class);
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
}
