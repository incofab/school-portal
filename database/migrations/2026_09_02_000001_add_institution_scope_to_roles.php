<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('roles', function (Blueprint $table) {
      $table
        ->foreignId('institution_id')
        ->nullable()
        ->after('id')
        ->constrained('institutions')
        ->cascadeOnDelete();

      $table
        ->text('description')
        ->nullable()
        ->after('name');
      $table
        ->boolean('default_permissions_seeded')
        ->default(false)
        ->after('description');

      $table->dropUnique('roles_name_guard_name_unique');
      $table->unique(
        ['institution_id', 'name', 'guard_name'],
        'roles_institution_name_guard_unique'
      );
    });

    Schema::table('institution_users', function (Blueprint $table) {
      $table->renameColumn('role', 'type');
    });
  }

  public function down(): void
  {
    Schema::table('institution_users', function (Blueprint $table) {
      $table->renameColumn('type', 'role');
    });

    Schema::table('roles', function (Blueprint $table) {
      $table->dropUnique('roles_institution_name_guard_unique');
      $table->dropConstrainedForeignId('institution_id');
      $table->unique(['name', 'guard_name']);
      $table->dropColumn(['description', 'default_permissions_seeded']);
    });
  }
};
