<?php

namespace App\Http\Requests;

use App\Enums\NotificationChannelsType;
use App\Enums\NotificationReceiversType;
use App\Models\Classification;
use App\Models\ReceiptType;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PaymentNotificationRequest extends FormRequest
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
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'receipt_type_id' => [
        'required',
        new ValidateExistsRule(ReceiptType::class)
      ],
      'receiver' => ['required', new Enum(NotificationReceiversType::class)],
      'classification_id' => [
        'nullable',
        new ValidateExistsRule(Classification::class)
      ],
      'channel' => ['required', new Enum(NotificationChannelsType::class)],
    ];
  }
}
