<?php

use App\Enums\InstitutionUserType;
use App\Models\InstitutionUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
  if (DB::getDriverName() === 'mysql') {
    $this->originalSqlMode = DB::selectOne(
      'SELECT @@SESSION.sql_mode AS mode'
    )->mode;
    DB::statement("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
  }
});

afterEach(function () {
  if (isset($this->originalSqlMode)) {
    DB::statement('SET SESSION sql_mode = ?', [$this->originalSqlMode]);
  }
});

it(
  'persists every user type and serializes the legacy role attribute',
  function (InstitutionUserType $type) {
    $institutionUser = InstitutionUser::factory()
      ->type($type)
      ->create()
      ->fresh();

    expect($institutionUser->type)
      ->toBe($type)
      ->and($institutionUser->role)
      ->toBe($type)
      ->and($institutionUser->toArray()['type'])
      ->toBe($type->value)
      ->and(json_decode($institutionUser->toJson(), true)['role'])
      ->toBe($type->value)
      ->and(Schema::hasColumn('institution_users', 'role'))
      ->toBeFalse();

    $this->assertDatabaseHas('institution_users', [
      'id' => $institutionUser->id,
      'type' => $type->value
    ]);
  }
)->with(InstitutionUserType::cases());

it('keeps the legacy role synchronized when the type changes', function () {
  $institutionUser = InstitutionUser::factory()
    ->type(InstitutionUserType::Student)
    ->create();
  $institutionUser->update(['type' => InstitutionUserType::Alumni]);

  expect($institutionUser->fresh()->role)
    ->toBe(InstitutionUserType::Alumni)
    ->and($institutionUser->fresh()->isAlumni())
    ->toBeTrue();
});

it('rejects null user types at the database level', function () {
  $institutionUser = InstitutionUser::factory()->create();

  expect(
    fn() => DB::table('institution_users')
      ->where('id', $institutionUser->id)
      ->update(['type' => null])
  )->toThrow(QueryException::class);
});

it('requires a type when inserting an institution user', function () {
  $institutionUser = InstitutionUser::factory()->create();

  expect(
    fn() => DB::table('institution_users')->insert([
      'institution_id' => $institutionUser->institution_id,
      'user_id' => $institutionUser->user_id
    ])
  )->toThrow(QueryException::class);
});

it(
  'renames existing user types without losing values and restores them on rollback',
  function () {
    $originalConnection = DB::getDefaultConnection();
    config([
      'database.connections.institution_type_migration' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => ''
      ]
    ]);
    DB::setDefaultConnection('institution_type_migration');

    try {
      Schema::create('institutions', function ($table) {
        $table->id();
      });
      Schema::create('roles', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->unique(['name', 'guard_name']);
      });
      Schema::create('institution_users', function ($table) {
        $table->id();
        $table->string('role');
      });
      $types = array_map(
        fn($type) => $type->value,
        InstitutionUserType::cases()
      );
      DB::table('institution_users')->insert(
        array_map(fn($type) => ['role' => $type], $types)
      );

      $migration = require database_path(
        'migrations/2026_09_02_000001_add_institution_scope_to_roles.php'
      );
      $migration->up();

      expect(Schema::hasColumn('institution_users', 'role'))
        ->toBeFalse()
        ->and(
          DB::table('institution_users')
            ->orderBy('id')
            ->pluck('type')
            ->all()
        )
        ->toBe($types);
      expect(
        fn() => DB::table('institution_users')->insert(['type' => null])
      )->toThrow(QueryException::class);

      $migration->down();

      expect(Schema::hasColumn('institution_users', 'type'))
        ->toBeFalse()
        ->and(
          DB::table('institution_users')
            ->orderBy('id')
            ->pluck('role')
            ->all()
        )
        ->toBe($types);
    } finally {
      DB::setDefaultConnection($originalConnection);
      DB::purge('institution_type_migration');
    }
  }
);
