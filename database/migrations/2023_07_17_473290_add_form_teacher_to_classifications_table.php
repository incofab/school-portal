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
      $table->unsignedBigInteger('form_teacher_id')->nullable();
      $table
        ->foreign('form_teacher_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('classifications', function (Blueprint $table) {
      $table->dropForeign(['form_teacher_id']);
      $table->dropColumn(['form_teacher_id']);
    });
  }
};
