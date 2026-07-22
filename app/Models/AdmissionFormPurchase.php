<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionFormPurchase extends BaseModel
{
  use HasFactory, SoftDeletes, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'admission_form_id' => 'integer',
    'paymentable_id' => 'integer'
  ];

  public function admissionForm()
  {
    return $this->belongsTo(AdmissionForm::class);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
