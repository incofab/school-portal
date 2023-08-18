<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class ValidateExistsRule implements ValidationRule
{
  private $model;
  function __construct(private string $table, private string $column = 'id')
  {
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $model = (new Model())
      ->setTable($this->table)
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
