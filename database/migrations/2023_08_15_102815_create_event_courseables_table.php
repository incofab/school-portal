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
    Schema::create('event_courseables', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('event_id');
      $table->morphs('courseable');

      $table->string('status')->default('active');
      $table->timestamps();

      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
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
    Schema::dropIfExists('event_courseables');
  }
};
