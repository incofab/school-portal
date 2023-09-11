<?php

namespace App\Http\Requests;

use App\Models\CourseSession;
use App\Models\TokenUser;
use App\Models\User;
use App\Rules\ValidateMorphRule;
use App\Support\MorphMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\ValidationException;
use Str;

class StoreExamRequest extends FormRequest
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
    // $student = currentInstitutionUser()?->student;
    return [
      'start_now' => ['nullable', 'boolean'],
      // 'external_reference' => [
      //   'string',
      //   new RequiredIf(empty($student)) // && !config('app.debug'))
      // ],
      'examable_id' => ['required', 'integer'],
      'examable_type' => [
        'required',
        new ValidateMorphRule('examable'),
        Rule::in(MorphMap::keys([User::class, TokenUser::class]))
      ],
      'courseables' => ['required', 'array', 'min:1'],
      'courseables.*.courseable_id' => ['required', 'integer'],
      'courseables.*.courseable_type' => [
        'required',
        new ValidateMorphRule('courseable'),
        Rule::in(MorphMap::keys([CourseSession::class]))
      ]
    ];
  }
}
