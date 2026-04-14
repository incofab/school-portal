<?php

namespace App\Http\Requests;

use App\Models\TheoryQuestion;
use Illuminate\Foundation\Http\FormRequest;

class TheoryQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var TheoryQuestion|null $theoryQuestion */
        $theoryQuestion = $this->route('theoryQuestion');

        return TheoryQuestion::createRule($theoryQuestion);
    }
}
