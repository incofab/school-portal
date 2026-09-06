<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole implements RoleContract
{
  use InstitutionScope;

  protected $casts = [
    'default_permissions_seeded' => 'boolean',
    'institution_id' => 'integer'
  ];

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  /**
   * Spatie's default users relation follows the guard's provider model.
   * Institution roles are assigned to InstitutionUser records instead.
   */
  public function institutionUsers(): BelongsToMany
  {
    return $this->morphedByMany(
      InstitutionUser::class,
      'model',
      config('permission.table_names.model_has_roles'),
      app(PermissionRegistrar::class)->pivotRole,
      config('permission.column_names.model_morph_key')
    );
  }

  public static function findByName(
    string $name,
    ?string $guardName = null
  ): RoleContract {
    $guardName ??= Guard::getDefaultName(static::class);

    $role = (new static())
      ->newQueryWithoutScopes()
      ->where('name', $name)
      ->where('guard_name', $guardName)
      ->whereNull('institution_id')
      ->first();

    if (!$role) {
      throw \Spatie\Permission\Exceptions\RoleDoesNotExist::named(
        $name,
        $guardName
      );
    }

    return $role;
  }

  public static function findOrCreate(
    string $name,
    ?string $guardName = null
  ): RoleContract {
    $guardName ??= Guard::getDefaultName(static::class);

    return (new static())->newQueryWithoutScopes()->firstOrCreate([
      'name' => $name,
      'guard_name' => $guardName,
      'institution_id' => null
    ]);
  }

  public static function findById(
    int|string $id,
    ?string $guardName = null
  ): RoleContract {
    $guardName ??= Guard::getDefaultName(static::class);

    $role = (new static())
      ->newQueryWithoutScopes()
      ->whereKey($id)
      ->where('guard_name', $guardName)
      ->whereNull('institution_id')
      ->first();

    if (!$role) {
      throw RoleDoesNotExist::withId($id, $guardName);
    }

    return $role;
  }

  public static function create(array $attributes = []): RoleContract
  {
    $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

    $query = (new static())
      ->newQueryWithoutScopes()
      ->where('name', $attributes['name'])
      ->where('guard_name', $attributes['guard_name']);

    if (array_key_exists('institution_id', $attributes)) {
      $query->where('institution_id', $attributes['institution_id']);
    } else {
      $query->whereNull('institution_id');
    }

    if ($query->exists()) {
      throw RoleAlreadyExists::create(
        $attributes['name'],
        $attributes['guard_name']
      );
    }

    return (new static())->newQueryWithoutScopes()->create($attributes);
  }
}
