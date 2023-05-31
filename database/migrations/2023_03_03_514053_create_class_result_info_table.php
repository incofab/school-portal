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
    Schema::create('class_result_info', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      // $table->unsignedBigInteger('course_id');
      $table->unsignedBigInteger('classification_id');
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->string('term')->nullable();
      $table->float('num_of_students')->default(0);
      $table->float('num_of_courses')->default(0);
      $table->float('total_score')->default(0);
      $table->float('max_obtainable_score')->default(0);
      $table->float('max_score')->default(0);
      $table->float('min_score')->default(0);
      $table->float('average')->default(0);

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();

      // $table
      //   ->foreign('course_id')
      //   ->references('id')
      //   ->on('courses')
      //   ->cascadeOnDelete();

      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();

      $table
        ->foreign('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('class_result_info');
  }
};
