<?php

namespace App\Http\Requests;

use App\Models\RecruitmentApplication;
use App\Models\VacancyPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RecruitmentApplicationRequest extends FormRequest
{
    private ?VacancyPost $vacancyPost = null;

    protected function prepareForValidation()
    {
        $this->vacancyPost = VacancyPost::query()
            ->isPublished()
            ->find($this->vacancy_post_id);

        if (! $this->vacancyPost) {
            throw ValidationException::withMessages([
                'vacancy_post_id' => 'Vacancy post not found or is no longer open',
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function getVacancyPost(): VacancyPost
    {
        return $this->vacancyPost;
    }

    public function rules(): array
    {
        return RecruitmentApplication::createRule();
    }
}
