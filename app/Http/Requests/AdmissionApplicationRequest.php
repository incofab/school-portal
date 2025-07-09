<?php

namespace App\Http\Requests;

use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AdmissionApplicationRequest extends FormRequest
{
  private ?AdmissionForm $admissionForm = null;
  function prepareForValidation()
  {
    $this->admissionForm = AdmissionForm::find($this->admission_form_id);
    if (!$this->admissionForm) {
      throw ValidationException::withMessages([
        'admission_form_id' => 'Admission form not found'
      ]);
    }
  }

  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  function getAdmissionForm(): AdmissionForm
  {
    return $this->admissionForm;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return AdmissionApplication::createRule();
  }
}
