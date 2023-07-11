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
    Schema::create('assessments', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->string('title');
      $table->string('description')->nullable();
      $table->float('max')->nullable();
      $table->string('term')->nullable();
      $table->boolean('for_mid_term')->nullable();
      $table->string('depends_on')->nullable();
      $table->softDeletes();

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
    Schema::dropIfExists('assessments');
  }
};
