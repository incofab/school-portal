<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('institution_groups', function (Blueprint $table) {
      $table
        ->string('website')
        ->nullable()
        ->after('name');

      $table
        ->string('banner')
        ->nullable()
        ->after('website');
    });
  }

  public function down(): void
  {
    Schema::table('institution_groups', function (Blueprint $table) {
      $table->dropColumn(['website', 'banner']);
    });
  }
};
