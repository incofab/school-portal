<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourseRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    $course = $this->course;
    return [
      'title' => [
        'required',
        Rule::unique('courses', 'title')
          ->where('institution_id', currentInstitution()->id)
          ->when($course, fn($q) => $q->ignore($course->id, 'id'))
      ],
      'code' => [
        'required',
        Rule::unique('courses', 'code')
          ->where('institution_id', currentInstitution()->id)
          ->when($course, fn($q) => $q->ignore($course->id, 'id'))
      ],
      'institution_id' => ['nullable'],
      'category' => ['nullable', 'string'],
      'description' => ['nullable', 'string']
    ];
  }
}
