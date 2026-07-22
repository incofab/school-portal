<?php

namespace App\Models;

use App\Enums\RecruitmentApplicationStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rules\Enum;

class RecruitmentApplication extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $appends = ['name'];

  protected $casts = [
    'institution_id' => 'integer',
    'vacancy_post_id' => 'integer',
    'status' => RecruitmentApplicationStatus::class
  ];

  public static function createRule(): array
  {
    return [
      'vacancy_post_id' => ['required', 'integer', 'exists:vacancy_posts,id'],
      'reference' => [
        'required',
        'string',
        'unique:recruitment_applications,reference'
      ],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'other_names' => ['nullable', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255'],
      'phone' => ['required', 'string', 'max:40'],
      'current_role' => ['nullable', 'string', 'max:255'],
      'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
      'highest_qualification' => ['nullable', 'string', 'max:255'],
      'cv_url' => ['nullable', 'url', 'max:2048'],
      'cover_letter' => [
        'nullable',
        'required_without:cover_letter_url',
        'string'
      ],
      'cover_letter_url' => [
        'nullable',
        'required_without:cover_letter',
        'url',
        'max:2048'
      ],
      'portfolio_url' => ['nullable', 'url', 'max:2048'],
      'available_from' => ['nullable', 'date']
    ];
  }

  public static function statusRule(): array
  {
    return [
      'status' => ['required', new Enum(RecruitmentApplicationStatus::class)],
      'review_note' => ['nullable', 'string']
    ];
  }

  public static function generateApplicationNo(): string
  {
    $prefix = 'REC' . date('Y');
    $key = $prefix . rand(100000, 999999);
    while (self::where('application_no', $key)->exists()) {
      $key = $prefix . rand(100000, 999999);
    }

    return $key;
  }

  protected function name(): Attribute
  {
    return Attribute::make(
      get: fn() => trim(
        "{$this->first_name} {$this->other_names} {$this->last_name}"
      )
    );
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function vacancyPost()
  {
    return $this->belongsTo(VacancyPost::class);
  }
}
