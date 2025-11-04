<?php

namespace App\Traits;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;

trait InstitutionScope
{
  protected static function boot()
  {
    parent::boot();

    static::addGlobalScope('institution', function (Builder $builder) {
      $institution = currentInstitution();
      if ($institution) {
        $table = (new self())->getTable();
        $builder->where($table . '.institution_id', $institution->id);
      }
    });
  }

  public function scopeForInstitution(Builder $query, Institution $institution)
  {
    return $query->where(
      $this->getTable() . '.institution_id',
      $institution->id
    );
  }
}
