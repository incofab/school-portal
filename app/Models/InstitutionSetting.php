<?php

namespace App\Models;

use App\Enums\InstitutionSettingType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Enum;

class InstitutionSetting extends Model
{
  use HasFactory, InstitutionScope;
  protected $guarded = [];

  static function storeRule($prefix = '')
  {
    return [
      $prefix . 'key' => ['required', new Enum(InstitutionSettingType::class)],
      $prefix . 'value' => ['nullable'],
      $prefix . 'photo' => [
        'nullable',
        'image',
        'mimes:jpg,png,jpeg',
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
