<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('class_result_info', function (Blueprint $table) {
      $table
        ->integer('whatsapp_message_count')
        ->default(0)
        ->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('class_result_info', function (Blueprint $table) {
      $table->dropColumn('whatsapp_message_count');
    });
  }
};
