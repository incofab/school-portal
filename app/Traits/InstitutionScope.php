<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait InstitutionScope
{
  protected static function boot()
  {
    parent::boot();

    static::addGlobalScope('institution', function (Builder $builder) {
      $institution = currentInstitution();
      if ($institution) {
        $builder->where('institution_id', $institution->id);
      }
    });
  }
}
