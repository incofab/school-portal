<?php

namespace App\Http\Requests;

use App\Enums\RoleGuard;
use App\Models\Role;
use App\Services\Institutions\InstitutionRoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionRoleRequest extends FormRequest
{
  public function authorize(): bool
  {
    return currentInstitutionUser()?->isAdmin() ?? false;
  }

  public function rules(): array
  {
    $role = $this->route('role');

    return [
      'name' => [
        'required',
        'string',
        'max:125',
        'regex:/\S/',
        Rule::unique('roles', 'name')
          ->where(
            fn($query) => $query
              ->where('institution_id', currentInstitution()?->id)
              ->where('guard_name', RoleGuard::Web->value)
          )
          ->ignore($role instanceof Role ? $role->id : null)
      ],
      'description' => ['nullable', 'string', 'max:1000'],
      'permissions' => ['nullable', 'array'],
      'permissions.*' => [
        'string',
        Rule::exists('permissions', 'name')->where(
          fn($query) => $query->where('guard_name', RoleGuard::Web->value)
        )
      ]
    ];
  }

  protected function prepareForValidation(): void
  {
    if ($this->has('name')) {
      $this->merge(['name' => preg_replace('/\s+/', ' ', trim($this->name))]);
    }
  }
}
