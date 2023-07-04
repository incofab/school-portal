<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('institution_settings', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->string('key');
      $table->text('value')->nullable();
      $table->string('display_name')->nullable();
      $table->string('type')->nullable();

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
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
    Schema::dropIfExists('institution_settings');
  }
};
