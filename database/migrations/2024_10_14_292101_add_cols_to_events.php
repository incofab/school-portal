<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('events', function (Blueprint $table) {
      $table->unsignedBigInteger('classification_id')->nullable();
      $table->unsignedBigInteger('classification_group_id')->nullable();
      $table->dateTime('expires_at')->nullable();

      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();
      $table
        ->foreign('classification_group_id')
        ->references('id')
        ->on('classification_groups')
        ->cascadeOnDelete();
    });
  }

  public function down()
  {
    Schema::table('events', function (Blueprint $table) {
      $table->dropForeign(['classification_group_id']);
      $table->dropForeign(['classification_id']);
      $table->dropColumn(
        'classification_group_id',
        'classification_id',
        'expires_at'
      );
    });
  }
};
