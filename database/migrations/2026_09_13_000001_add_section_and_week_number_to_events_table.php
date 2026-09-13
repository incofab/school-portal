<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table
        ->foreignId('class_division_id')
        ->nullable()
        ->after('classification_id')
        ->constrained('class_divisions')
        ->nullOnDelete();
      $table
        ->string('week_number')
        ->nullable()
        ->after('term');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table->dropForeign(['class_division_id']);
      $table->dropColumn(['class_division_id', 'week_number']);
    });
  }
};
