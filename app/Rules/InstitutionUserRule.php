<?php

namespace App\Rules;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstitutionUserRule implements ValidationRule
{
  public function __construct(
    private Institution $institution,
    private InstitutionUserType $role
  ) {
    //
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $user = User::query()
      ->join('institution_users', 'institution_users.user_id', 'users.id')
      ->where('users.id', $value)
      ->where('institution_users.institution_id', $this->institution->id)
      ->when(
        $this->role,
        fn($q) => $q->where('institution_users.role', $this->role)
      )
      ->first();

    if (!$user) {
      $fail('user does not exist');
      return;
    }
  }
}
