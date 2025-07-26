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
    Schema::create('topics', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('course_id');
      $table->string('title');
      $table->text('description')->nullable(true);

      $table->timestamps();

      $table
        ->foreign('course_id')
        ->references('id')
        ->on('courses')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('topics');
  }
};
