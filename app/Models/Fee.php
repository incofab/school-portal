<?php

namespace App\Models;

use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Support\MorphMap;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = [
    'payment_interval' => PaymentInterval::class,
    'institution_id' => 'integer',
    'amount' => 'float',
    'academic_session_id' => 'integer',
    'term' => TermType::class,
    'fee_items' => AsArrayObject::class
  ];

  function isSessional(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly ||
      $this->payment_interval === PaymentInterval::Sessional;
  }

  function isTermly(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly;
  }

  /** @deprecated */
  static function scopeForClass($query, Classification $classification)
  {
    return $query->where(function ($qq) use ($classification) {
      $qq
        ->where(
          fn($q) => $q
            ->whereNull('classification_group_id')
            ->whereNull('classification_id')
        )
        ->orWhere(
          fn($q) => $q
            ->whereNotNull('classification_group_id')
            ->where(
              'classification_group_id',
              $classification->classification_group_id
            )
        )
        ->orWhere(
          fn($q) => $q
            ->whereNull('classification_group_id')
            ->where('classification_id', $classification->id)
        );
    });
  }

  function forStudent(Student $student, Classification $classification)
  {
    $isForStudent = false;
    foreach ($this->feeCategories as $key => $feeCategory) {
      if ($feeCategory->feeable_type == MorphMap::key(Institution::class)) {
        $isForStudent = true;
        break;
      }
      // Check association
      if ($feeCategory->feeable_type == MorphMap::key(Classification::class)) {
        $isForStudent = $feeCategory->feeable_id == $classification->id;
        break;
      }
      if (
        $feeCategory->feeable_type == MorphMap::key(ClassificationGroup::class)
      ) {
        $isForStudent =
          $feeCategory->feeable_id == $classification->classification_group_id;
        break;
      }
    }
    return $isForStudent;
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  function feeCategories()
  {
    return $this->hasMany(FeeCategory::class);
  }

  function feePayments()
  {
    return $this->hasMany(FeePayment::class);
  }

  function receipts()
  {
    return $this->hasMany(Receipt::class);
  }
}
