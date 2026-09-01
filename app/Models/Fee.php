<?php

namespace App\Models;

use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends BaseModel
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

  public function isSessional(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly ||
      $this->payment_interval === PaymentInterval::Sessional;
  }

  public function isTermly(): bool
  {
    return $this->payment_interval === PaymentInterval::Termly;
  }

  // /** @deprecated */
  // static function scopeForClass($query, Classification $classification)
  // {
  //   return $query->where(function ($qq) use ($classification) {
  //     $qq
  //       ->where(
  //         fn($q) => $q
  //           ->whereNull('classification_group_id')
  //           ->whereNull('classification_id')
  //       )
  //       ->orWhere(
  //         fn($q) => $q
  //           ->whereNotNull('classification_group_id')
  //           ->where(
  //             'classification_group_id',
  //             $classification->classification_group_id
  //           )
  //       )
  //       ->orWhere(
  //         fn($q) => $q
  //           ->whereNull('classification_group_id')
  //           ->where('classification_id', $classification->id)
  //       );
  //   });
  // }

  public function forStudent(Student $student, ?Classification $classification)
  {
    $isForStudent = false;
    foreach ($this->feeCategories as $key => $feeCategory) {
      if (
        $feeCategory->forClass($classification) ||
        $feeCategory->forStudent($student)
      ) {
        $isForStudent = true;
        break;
      }

      // Check association
    }

    return $isForStudent;
  }

  public function forClass(?Classification $classification)
  {
    foreach ($this->feeCategories as $key => $feeCategory) {
      if ($feeCategory->forClass($classification)) {
        return true;
      }
    }

    return false;
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  public function feeCategories()
  {
    return $this->hasMany(FeeCategory::class);
  }

  public function feePayments()
  {
    return $this->hasMany(FeePayment::class);
  }

  public function receipts()
  {
    return $this->hasMany(Receipt::class);
  }
}
