<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreExpenseRequest extends FormRequest
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
            'title' => ['required','string','max:255','min:3'],
            'description' => ['nullable','string','max:500'],
            'amount' => ['required','numeric','min:0.01','max:999999.99'],
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
            'term' => ['nullable', new Enum(TermType::class)],
            'expense_date' => ['required','date','before_or_equal:today'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The expense title is required.',
            'title.min' => 'The expense title must be at least 3 characters.',
            'title.max' => 'The expense title cannot exceed 255 characters.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'amount.required' => 'The expense amount is required.',
            'amount.numeric' => 'The expense amount must be a valid number.',
            'amount.min' => 'The expense amount must be greater than 0.',
            'amount.max' => 'The expense amount cannot exceed 999,999.99.',
            'expense_date.required' => 'The expense date is required.',
            'expense_date.date' => 'The expense date must be a valid date.',
            'expense_date.before_or_equal' => 'The expense date cannot be in the future.',
            'category_id.required' => 'Please select an expense category.',
            'category_id.integer' => 'The selected expense category is invalid.',
            'category_id.exists' => 'The selected expense category is invalid.',
            'academic_session_id.integer' => 'The selected academic session is invalid.',
            'academic_session_id.exists' => 'The selected academic session is invalid.',
            'term.string' => 'The selected term must be a valid text value.',
            'term.in' => 'The selected term must be first, second, or third.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'expense title',
            'description' => 'description',
            'amount' => 'amount',
            'academic_session_id' => 'academic session',
            'term' => 'academic term',
            'expense_date' => 'expense date',
            'category_id' => 'expense category'
        ];
    }
}