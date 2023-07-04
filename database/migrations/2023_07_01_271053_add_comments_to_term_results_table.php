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
      $table->text('teacher_comment')->nullable();
      $table->text('principal_comment')->nullable();
      $table->text('general_comment')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('term_results', function (Blueprint $table) {
      $table->dropColumn([
        'teacher_comment',
        'principal_comment',
        'general_comment'
      ]);
    });
  }
};
