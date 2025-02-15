<?php

namespace App\Rules;

use App\Models\Funding;
use App\Models\Transaction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateFundingReference implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $debtReference = Funding::debtReference($value);
        $creditReference = Funding::creditReference($value);
        $existingFunding = Funding::where('reference', $debtReference)
            ->orWhere('reference', $creditReference)
            ->exists();
        $existingTransaction = Transaction::where('reference', $debtReference)
            ->orWhere('reference', $creditReference)
            ->exists();
        if ($existingFunding || $existingTransaction) {
            $fail('Invalid reference');
            return;
        }
    }
}
