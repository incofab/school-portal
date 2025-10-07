<?php

namespace App\Models;

use App\Enums\AttendanceType;
use App\Rules\ValidateExistsRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Attendance extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'institution_user_id' => 'integer',
    'institution_staff_user_id' => 'integer',
    'signed_in_at' => 'datetime',
    'signed_out_at' => 'datetime'
  ];

  static function createRule()
  {
    return [
      'institution_user_id' => [
        'required',
        new ValidateExistsRule(InstitutionUser::class, 'id')
      ],
      'remark' => ['nullable', 'string'],
      'type' => ['required', Rule::in(AttendanceType::values())],
      'reference' => [
        'required_if:type,' . AttendanceType::In->value,
        function ($attr, $value, $fail) {
          if (request()->type !== AttendanceType::In->value) {
            return;
          }
          if (Attendance::where('reference', $value)->exists()) {
            $fail('Reference must me unique, or attendance already recorded.');
          }
        }
      ]
    ];
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class, 'institution_user_id');
  }

  public function staffUser()
  {
    return $this->belongsTo(
      InstitutionUser::class,
      'institution_staff_user_id'
    );
  }
}
