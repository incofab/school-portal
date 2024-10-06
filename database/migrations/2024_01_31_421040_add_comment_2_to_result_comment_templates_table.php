<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('result_comment_templates', function (Blueprint $table) {
      $table->text('comment_2')->nullable();
    });
  }

  public function down()
  {
    Schema::table('result_comment_templates', function (Blueprint $table) {
      $table->dropColumn(['comment_2']);
    });
  }
};
