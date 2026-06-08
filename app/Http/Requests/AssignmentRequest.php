<?php

namespace App\Http\Requests;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\InstitutionUser;
use App\Rules\ValidateExistsRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AssignmentRequest extends FormRequest
{
    private ?InstitutionUser $institutionUser;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getInstitutionUser(): InstitutionUser
    {
        return $this->institutionUser;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', new ValidateExistsRule(Course::class)],
            'academic_session_id' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                new ValidateExistsRule(AcademicSession::class),
            ],
            'term' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                new Enum(TermType::class),
            ],
            'max_score' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string'],
            'expires_at' => ['required', 'date', 'after:now'],
            'classification_ids' => ['required', 'array', 'min:1'],
            'classification_ids.*' => ['required', new ValidateExistsRule(Classification::class)],
            'institution_user_id' => ['required', function ($attr, $value, $fail) {
                $this->institutionUser = $instUser = InstitutionUser::query()->where('id', $value)->first();
                if (! $instUser) {
                    $fail('Institution user record not found');

                    return;
                }
                if (! $instUser->isTeacher() && ! $instUser->isAdmin()) {
                    $fail('The selected user is not a teacher/admin');

                    return;
                }

                if ($instUser->isAdmin()) {
                    return;
                }

                $courseTeacher = CourseTeacher::query()
                    ->whereIn('classification_id', $this->classification_ids)
                    ->where('course_id', $this->course_id)
                    ->first();
                if (! $courseTeacher) {
                    $fail('Unauthorized course teacher');

                    return;
                }
            }],
        ];
    }
}
