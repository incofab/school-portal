<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('academic_sessions', function (Blueprint $table) {
      $table->unsignedTinyInteger('order_index')->default(100);
    });
  }

  public function down()
  {
    Schema::table('academic_sessions', function (Blueprint $table) {
      $table->dropColumn(['order_index']);
    });
  }
};
