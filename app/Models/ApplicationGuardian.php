<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationGuardian extends BaseModel
{
  use HasFactory;

  public $guarded = [];
  protected $table = 'application_guardians';
  protected $casts = [
    'admission_application_id' => 'integer'
  ];

  // Define the relationship with AdmissionApplication
  public function admissionApplication()
  {
    return $this->belongsTo(
      AdmissionApplication::class,
      'admission_application_id'
    );
  }
}
