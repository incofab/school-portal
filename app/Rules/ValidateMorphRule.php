<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ValidateMorphRule implements ValidationRule
{
  private $morphType;
  private $morphId;
  private $morphModel;
  function __construct(private string $morphName)
  {
    // $this->morphType = request("{$this->morphName}_type");
    // $this->morphId = request("{$this->morphName}_id");
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $this->morphType = request($attribute);
    $this->morphId = request(
      substr($attribute, 0, strlen($attribute) - 5) . '_id'
    );

    if (empty($this->morphType) || empty($this->morphId)) {
      $fail("{$this->morphName} is required");
      return;
    }

    $modelClass = Relation::getMorphedModel($this->morphType);
    if (!$modelClass) {
      $fail("{$this->morphType} is an invalid {$this->morphName}");
      return;
    }

    $model = (new $modelClass())->find($this->morphId);

    if (!$model) {
      $fail("{$this->morphName} class not found");
      return;
    }

    $this->morphModel = $model;
  }

  function getModel(): Model
  {
    return $this->morphModel;
  }
}
