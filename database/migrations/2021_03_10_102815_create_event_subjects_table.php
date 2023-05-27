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
    Schema::create('event_subjects', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('event_id');
      $table->unsignedBigInteger('course_session_id')->nullable(true);

      $table->string('status')->default('active');
      $table->timestamps();

      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
        ->cascadeOnDelete();

      $table
        ->foreign('course_session_id')
        ->references('id')
        ->on('course_sessions')
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
    Schema::dropIfExists('event_subjects');
  }
};
