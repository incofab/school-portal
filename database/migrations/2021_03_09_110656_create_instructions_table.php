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
    Schema::create('instructions', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('course_session_id');
      $table->text('instruction');
      $table->unsignedInteger('from');
      $table->unsignedInteger('to');

      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
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
    Schema::dropIfExists('instructions');
  }
};
