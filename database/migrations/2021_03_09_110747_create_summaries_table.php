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
    Schema::create('summaries', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('course_id');
      $table->string('chapter_no');
      $table->text('title')->nullable(true);
      $table->text('description')->nullable(true);
      $table->text('summary')->nullable(true);

      $table
        ->foreign('course_id')
        ->references('id')
        ->on('courses')
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
    Schema::dropIfExists('summaries');
  }
};
