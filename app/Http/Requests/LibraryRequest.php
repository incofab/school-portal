<?php

namespace App\Http\Requests;

use App\Enums\LibrarySourceType;
use App\Models\InstitutionUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class LibraryRequest extends FormRequest
{
    private ?InstitutionUser $institutionUser = null;

    public function authorize(): bool
    {
        return true;
    }

    public function getInstitutionUser(): InstitutionUser
    {
        return $this->institutionUser;
    }

    public function rules(): array
    {
        $institution = $this->route('institution');
        $library = $this->route('library');
        $sourceType = $this->input('source_type', LibrarySourceType::Upload->value);
        $hasExistingUpload = (bool) (
            $library?->file_url ||
            $library?->file_path
        );

        return [
            'title' => ['required', 'string', 'max:255'],
            'material_type' => ['required', 'string', 'max:50'],
            'source_type' => ['required', new Enum(LibrarySourceType::class)],
            'course_id' => [
                'nullable',
                Rule::exists('courses', 'id')->where('institution_id', $institution->id),
            ],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'external_url' => [
                Rule::requiredIf($sourceType === LibrarySourceType::External->value),
                'nullable',
                'url',
                'max:2048',
            ],
            'file' => [
                Rule::requiredIf(
                    $sourceType === LibrarySourceType::Upload->value &&
                      ! $hasExistingUpload
                ),
                'nullable',
                'file',
                'max:1024',
            ],
            'classification_ids' => ['nullable', 'array'],
            'classification_ids.*' => [
                'required',
                Rule::exists('classifications', 'id')->where(
                    'institution_id',
                    $institution->id
                ),
            ],
            'institution_user_id' => [
                'required',
                function ($attr, $value, $fail) use ($institution) {
                    $this->institutionUser = InstitutionUser::query()
                        ->where('id', $value)
                        ->where('institution_id', $institution->id)
                        ->first();

                    if (! $this->institutionUser) {
                        $fail('Institution user record not found');

                        return;
                    }

                    if (
                        ! $this->institutionUser->isTeacher() &&
                        ! $this->institutionUser->isAdmin()
                    ) {
                        $fail('Only teachers and admins can create library materials.');
                    }
                },
            ],
        ];
    }
}
