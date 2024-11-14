<?php
namespace App\Actions\Sheet;

use Closure;

class SheetValueHandler
{
  function __construct(public string $key, private ?Closure $callback)
  {
  }

  function handleValue($value)
  {
    $callback = $this->callback;
    if ($callback) {
      return $callback($value);
    }
    return $value;
  }
}
