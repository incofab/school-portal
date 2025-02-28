<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * The main reason for this rule over the Laravel "unique" rule is that it will instantiate the model
 * which will inturn apply the InstitutionScope Trait (And any other model based operations).
 * it also gives you access to the validated model
 */
class ValidateUniqueRule implements ValidationRule
{
  function __construct(
    private string $modelClass,
    private string $column = 'reference'
  ) {
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $model = (new $this->modelClass())
      ->query()
      ->where($this->column, $value)
      ->first();

    if ($model) {
      $fail("$attribute must be unique");
      return;
    }
  }
}
