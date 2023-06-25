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
    Schema::table('classifications', function (Blueprint $table) {
      $table
        ->boolean('has_equal_subjects')
        ->comment(
          'Indication to show if all students in the class offer the same number of subjects'
        )
        ->default(true);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('classifications', function (Blueprint $table) {
      $table->dropColumn('has_equal_subjects');
    });
  }
};
