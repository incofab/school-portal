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
    Schema::table('term_results', function (Blueprint $table) {
      $table->float('height')->nullable();
      $table->float('weight')->nullable();
      $table->integer('attendance_count')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('term_results', function (Blueprint $table) {
      $table->dropColumn(['height', 'weight', 'attendance_count']);
    });
  }
};
