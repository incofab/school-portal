<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'admission_form_id' => 'integer',
    'admission_form_purchase_id' => 'integer'
  ];

  static function generateApplicationNo()
  {
    $prefix = date('Y');
    $key = $prefix . rand(1000000, 9999999);
    while (self::where('application_no', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }
    return $key;
  }

  function hasBeenPaid(): bool
  {
    if (intval($this->admissionForm?->price) <= 0) {
      return true;
    }
    return empty($this->admission_form_purchase_id);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function admissionForm()
  {
    return $this->belongsTo(AdmissionForm::class);
  }

  public function admissionFormPurchase()
  {
    return $this->belongsTo(AdmissionFormPurchase::class);
  }

  public function applicationGuardians()
  {
    return $this->hasMany(
      ApplicationGuardian::class,
      'admission_application_id'
    );
  }
}
