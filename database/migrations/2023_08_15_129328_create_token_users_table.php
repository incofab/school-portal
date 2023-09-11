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
    Schema::create('token_users', function (Blueprint $table) {
      $table->id();

      $table
        ->string('reference')
        ->unique()
        ->index();
      $table->string('email')->nullable(true);
      $table->string('phone')->nullable(true);
      $table->string('name')->nullable(true);

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
    Schema::dropIfExists('token_users');
  }
};
