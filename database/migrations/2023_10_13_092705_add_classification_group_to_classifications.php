<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('classifications', function (Blueprint $table) {
      $table->unsignedBigInteger('classification_group_id')->nullable();

      $table
        ->foreign('classification_group_id')
        ->references('id')
        ->on('classification_groups')
        ->cascadeOnDelete();
    });
  }

  public function down()
  {
    Schema::table('classifications', function (Blueprint $table) {
      $table->dropForeign(['classification_group_id']);
      $table->dropColumn('classification_group_id');
    });
  }
};
