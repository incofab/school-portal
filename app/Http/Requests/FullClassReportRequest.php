<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class FullClassReportRequest extends FormRequest
{
  public ?Classification $classificationObj = null;
  public ?AcademicSession $academicSessionObj = null;
  public ?TermType $termObj = null;

  protected function prepareForValidation()
  {
    if (!$this->academicSession || !$this->classification || !$this->term) {
      return;
    }

    $this->classificationObj = Classification::query()
      ->where('id', $this->classification)
      ->first();
    $this->academicSessionObj = AcademicSession::query()
      ->where('id', $this->academicSession)
      ->first();
    $this->termObj = TermType::tryFrom($this->term);

    if (
      !$this->classificationObj ||
      !$this->academicSessionObj ||
      !$this->termObj
    ) {
      throw ValidationException::withMessages([
        'classification' => 'Select a class, academic session, and term',
        'academicSession' => 'Select a class, academic session, and term',
        'term' => 'Select a class, academic session, and term'
      ]);
    }
  }

  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'classification' => ['nullable', 'integer'],
      'academicSession' => ['nullable', 'integer'],
      'term' => ['nullable', new Enum(TermType::class)]
    ];
  }
}
