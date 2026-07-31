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
      'file' => [
        'required',
        'file',
        'mimes:jpg,jpeg,png,webp,pdf,doc,docx,mp4,mov,avi,mkv,mp3,wav',
        'max:10240'
      ]
    ];
  }
}
