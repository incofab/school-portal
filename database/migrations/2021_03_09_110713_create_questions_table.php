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
    Schema::create('questions', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('course_session_id');
      $table->unsignedBigInteger('topic_id')->nullable(true);

      $table->unsignedInteger('question_no');
      $table->text('question');
      $table->text('option_a');
      $table->text('option_b');
      $table->text('option_c');
      $table->text('option_d')->nullable(true);
      $table->text('option_e')->nullable(true);
      $table->string('answer', 2);
      $table->longText('answer_meta')->nullable(true);

      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
        ->onDelete('cascade')
        ->onUpdate('cascade');

      $table
        ->foreign('topic_id')
        ->references('id')
        ->on('topics')
        ->onDelete('cascade')
        ->onUpdate('cascade');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('questions');
  }
};
