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
    Schema::create('classifications', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('institution_id');
      $table->string('title');
      $table->text('description')->nullable(true);
      $table
        ->boolean('has_equal_subjects')
        ->comment(
          'Indication to show if all students in the class offer the same number of subjects'
        )
        ->default(true);
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
    Schema::dropIfExists('classifications');
  }
};
