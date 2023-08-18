<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('exam_courseables', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('exam_id');
      $table->morphs('courseable');
      $table->unsignedInteger('score', false, true)->nullable(true);
      $table->unsignedInteger('num_of_questions', false, true)->nullable(true);
      $table->string('status')->default('active');
      $table->timestamps();

      $table
        ->foreign('exam_id')
        ->references('id')
        ->on('exams')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('exam_courseables');
  }
};
