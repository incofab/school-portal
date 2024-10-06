<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('token_users', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->nullable();
      $table->unsignedBigInteger('institution_id');
      $table->text('meta')->nullable();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
    });
  }

  public function down()
  {
    Schema::table('token_users', function (Blueprint $table) {
      $table->dropForeign(['institution_id']);
      $table->dropForeign(['user_id']);
      $table->dropColumn(['institution_id', 'user_id', 'meta']);
    });
  }
};
