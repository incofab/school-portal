<?php

namespace App\Models;

use App\Enums\InstitutionSettingType;
use App\Traits\HasMedia;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rules\Enum;

class InstitutionSetting extends BaseModel
{
  use HasFactory, HasMedia, InstitutionScope;

  protected $guarded = [];

  public static function storeRule($prefix = '')
  {
    return [
      $prefix . 'key' => ['required', new Enum(InstitutionSettingType::class)],
      $prefix . 'value' => ['nullable'],
      $prefix . 'photo' => [
        'nullable',
        'image',
        'mimes:jpg,png,jpeg,webp',
        'max:1024'
      ],
      $prefix . 'display_name' => ['nullable', 'string'],
      $prefix . 'type' => ['nullable', 'string']
    ];
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
