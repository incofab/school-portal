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
    Schema::create('students', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('institution_user_id');
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('classification_id');
      $table->string('code')->unique();
      $table->string('guardian_phone')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table
        ->foreign('institution_user_id')
        ->references('id')
        ->on('institution_users')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
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
    Schema::dropIfExists('students');
  }
};
