<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionForm extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'is_published' => 'boolean'
  ];

  static function createRule(AdmissionForm|null $admissionForm = null)
  {
    return [
      'title' => ['required', 'string'],
      'description' => ['nullable', 'string'],
      'price' => ['required', 'numeric', 'min:0'],
      'is_published' => ['sometimes', 'boolean'],
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', 'string']
    ];
  }

  function scopeIsPublished($query, $isPublished = true)
  {
    return $query->where('is_published', $isPublished);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  public function admissionFormPurchases()
  {
    return $this->hasMany(AdmissionFormPurchase::class);
  }
}
