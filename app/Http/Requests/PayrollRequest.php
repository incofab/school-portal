<?php

namespace App\Http\Requests;

use App\Enums\YearMonth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PayrollRequest extends FormRequest
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
            'month' => ['required', 'string', new Enum(YearMonth::class)],
            'year'  => 'required|integer|min:2023|max:' . $currentYear,
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'The month is required.',
            'month.enum' => 'The selected month is invalid.',

            'year.required'  => 'The year is required.',
            'year.integer'   => 'The year must be a number.',
            'year.min'       => 'The year must be at least :min.',
            'year.max'       => 'The year must not be greater than :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'month' => 'month',
            'year'  => 'year',
        ];
    }
}
