<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\TermType;
use App\Rules\ValidateExistsRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class Event extends Model
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'status' => EventStatus::class,
    'starts_at' => 'datetime',
    'expires_at' => 'datetime',
    'transferred_at' => 'datetime',
    'institution_id' => 'integer',
    'num_of_subjects' => 'integer',
    'num_of_activations' => 'integer',
    'classification_id' => 'integer',
    'classification_group_id' => 'integer',
    'show_corrections' => 'boolean',
    'academic_session_id' => 'integer',
    'duration' => 'float',
    'term' => TermType::class,
    'type' => EventType::class,
    'for_mid_term' => 'boolean'
  ];

  static function createRule(Event|null $event = null)
  {
    return [
      'title' => [
        'required',
        'string',
        Rule::unique('events', 'id')
          ->where('institution_id', currentInstitution()->id)
          ->when($event, fn($q) => $q->ignore($event->id, 'id'))
      ],
      'description' => ['nullable', 'string'],
      'duration' => ['required', 'numeric'],
      'starts_at' => ['required', 'date'],
      'expires_at' => ['nullable', 'date'],
      'num_of_subjects' => ['nullable', 'integer'],
      'num_of_activations' => ['nullable', 'integer'],
      'show_corrections' => ['sometimes', 'boolean'],
      'type' => ['required', new Enum(EventType::class)],
      'classification_group_id' => [
        'nullable',
        new ValidateExistsRule(ClassificationGroup::class)
      ],
      'classification_id' => [
        'nullable',
        new ValidateExistsRule(Classification::class)
      ]
    ];
  }

  static function generateCode()
  {
    $key = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    while (self::where('code', '=', $key)->first()) {
      $key = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }
    return $key;
  }

  public function duration(): Attribute
  {
    return Attribute::make(
      get: fn($value) => $value ? floor($value / 60) : null,
      set: fn($value) => $value ? $value * 60 : null
    );
  }
  function getDurationInSeconds()
  {
    return $this->getRawOriginal('duration');
  }
  function scopeActive($query, $status = 'active')
  {
    return $query->where('status', $status);
  }

  function scopeForStudent($query, ?Student $student = null)
  {
    if (!$student) {
      return $query;
    }
    if (!$student->classification->classification_group_id) {
      return $query->where('classification_id', $student->classification_id);
    }
    return $query
      ->where(
        fn($q) => $q
          ->where(
            'classification_group_id',
            '=',
            $student->classification->classification_group_id
          )
          ->whereNull('classification_id')
      )
      ->orWhere('classification_id', $student->classification_id);
  }

  function canCreateExamCheck()
  {
    if ($this->status !== EventStatus::Active) {
      return [false, 'Event is not active'];
    }
    if ($this->starts_at->greaterThan(now())) {
      return [false, "It's not yet time"];
    }

    if ($this->eventCourseables()->count() < $this->num_of_subjects) {
      return [
        false,
        'Event is not ready yet. It does not contain enough subjects'
      ];
    }
    return [true, ''];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function exams()
  {
    return $this->hasMany(Exam::class);
  }

  function eventCourseables()
  {
    return $this->hasMany(EventCourseable::class);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }
}
