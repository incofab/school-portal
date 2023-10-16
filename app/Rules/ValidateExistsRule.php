<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * The main reason for this rule over the Laravel "exists" rule is that it will instantiate the model
 * which will inturn apply the InstitutionScope Trait (And any other model based operations).
 * it also gives you access to the validated model
 */
class ValidateExistsRule implements ValidationRule
{
  private $model;
  function __construct(
    private string $modelClass,
    private string $column = 'id'
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

    if (!$model) {
      $fail("$attribute does not exist");
      return;
    }

    $this->model = $model;
  }

  function getModel(): Model
  {
    return $this->model;
  }
}
