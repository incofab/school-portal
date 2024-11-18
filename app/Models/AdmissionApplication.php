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
  ];


  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function applicationGuardians()
  {
    return $this->hasMany(ApplicationGuardian::class, 'admission_application_id');
  }
}
