<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('class_result_info', function (Blueprint $table) {
      $table->date('next_term_resumption_date')->nullable();
    });
  }

  public function down()
  {
    Schema::table('class_result_info', function (Blueprint $table) {
      $table->dropColumn(['next_term_resumption_date']);
    });
  }
};
