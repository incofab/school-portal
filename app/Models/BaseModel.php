<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
  public function freshWithLockForUpdate(
    $with = [],
    bool $withoutGlobalScopes = false
  ) {
    $query = $withoutGlobalScopes
      ? $this->newQueryWithoutScopes()
      : $this->newQuery();
    $relations = is_string($with)
      ? array_filter(func_get_args(), 'is_string')
      : $with;

    return $query
      ->whereKey($this->getKey())
      ->lockForUpdate()
      ->with($relations)
      ->firstOrFail();
  }
}
