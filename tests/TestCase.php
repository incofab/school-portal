<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
  use CreatesApplication;

  protected function setUp(): void
  {
    parent::setUp();

    $this->withoutVite();
    request()->setRouteResolver(fn() => null);

    $seederClasses = [
      \Database\Seeders\RoleSeeder::class,
      \Database\Seeders\PermissionInventorySeeder::class
    ];

    foreach ($seederClasses as $seeder) {
      Artisan::call('db:seed', ['--class' => $seeder]);
    }

    // app(
    //   \App\Services\Institutions\InstitutionRoleService::class
    // )->ensurePermissionInventory();
  }

  /**
   * Runs once after the whole test suite finishes.
   */
  public static function tearDownAfterClass(): void
  {
    // Artisan::call('db:wipe');

    parent::tearDownAfterClass();
  }
}
