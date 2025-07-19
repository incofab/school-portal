<?php

namespace App\Http\Requests;

use App\Enums\YearMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SalaryAdjustmentRequest extends FormRequest
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
        $currentYear = date('Y');

        return [
            'adjustment_type_id' => 'required|integer|exists:adjustment_types,id',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'month' => ['required', 'string', new Enum(YearMonth::class)],
            'year'  => 'required|integer|min:2023|max:' . $currentYear + 2,
            'institution_user_id' => 'required|integer|exists:institution_users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_type_id.required' => 'Please select an adjustment type.',
            'adjustment_type_id.integer' => 'The adjustment type ID must be a valid number.',
            'adjustment_type_id.exists' => 'The selected adjustment type does not exist.',

            'description.string' => 'The description must be a string.',
            'description.max' => 'The description may not be greater than 255 characters.',

            'amount.required' => 'The adjustment amount is required.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be at least 0.',

            'month.required' => 'The month is required.',
            'month.enum' => 'The selected month is invalid.',

            'year.required'  => 'The year is required.',
            'year.integer'   => 'The year must be a number.',
            'year.min'       => 'The year must be at least :min.',
            'year.max'       => 'The year must not be greater than :max.',

            'institution_user_id.required' => 'Please select a staff member.',
            'institution_user_id.integer' => 'The staff ID must be a valid number.',
            'institution_user_id.exists' => 'The selected staff member does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'adjustment_type_id' => 'adjustment type',
            'description' => 'description',
            'amount' => 'adjustment amount',
            'month' => 'month',
            'year'  => 'year',
            'institution_user_id' => 'staff member',
        ];
    }
}
