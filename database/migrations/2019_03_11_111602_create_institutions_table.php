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
    Schema::create('institutions', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->uuid('uuid')->index();
      $table->unsignedBigInteger('user_id');

      $table->string('code')->unique();
      $table->string('name');
      $table->string('photo')->nullable(true);
      $table->string('address')->nullable(true);
      $table->string('phone')->nullable(true);
      $table->string('email')->nullable(true);
      $table->string('status')->default('active');

      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

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
    Schema::dropIfExists('institutions');
  }
};
