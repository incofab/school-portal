<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClassMovement extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'user_id' => 'integer',
    'institution_id' => 'integer',
    'student_id' => 'integer',
    'source_classification_id' => 'integer',
    'destination_classification_id' => 'integer'
  ];

  public function sourceClass()
  {
    return $this->belongsTo(Classification::class);
  }

  public function destinationClass()
  {
    return $this->belongsTo(Classification::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
