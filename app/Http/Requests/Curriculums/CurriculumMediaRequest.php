<?php

namespace App\Http\Requests\Curriculums;

use Illuminate\Foundation\Http\FormRequest;

class CurriculumMediaRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'file' => self::fileRules()
    ];
  }

  public static function fileRules(bool $required = true): array
  {
    return [
      $required ? 'required' : 'nullable',
      'file',
      'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4,mov,avi,mkv,mp3,wav',
      'max:10240'
    ];
  }
}
