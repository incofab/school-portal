<?php

namespace App\Http\Requests;

use App\Enums\FaqType;
use App\Support\Faq\FaqContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->isAdmin() ?? false;
  }

  public function rules(): array
  {
    $faq = $this->route('faq');

    return [
      'name' => ['required', 'string', 'max:255'],
      'type' => ['sometimes', Rule::enum(FaqType::class)],
      'code' => [
        'required',
        'string',
        'max:255',
        'alpha_dash',
        Rule::unique('faqs', 'code')->ignore($faq?->id)
      ],
      'description' => ['required', 'string'],
      'video_url' => [
        'nullable',
        'url',
        'max:2048',
        function ($attribute, $value, $fail) {
          if (filled($value) && !FaqContent::isValidYoutubeUrl($value)) {
            $fail(
              'The video URL must be a valid YouTube watch, youtu.be, Shorts, embed, or live URL.'
            );
          }
        }
      ],
      'is_active' => ['sometimes', 'boolean'],
      'sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295']
    ];
  }
}
