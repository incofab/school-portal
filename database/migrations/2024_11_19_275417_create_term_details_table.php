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
    Schema::create('term_details', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreignId('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->cascadeOnDelete();
      $table->string('term');
      $table->boolean('for_mid_term')->default(false);
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->integer('expected_attendance_count')->nullable();
      $table->boolean('is_activated')->default(false);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('term_details');
  }
};
