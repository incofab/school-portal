<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('result_publications', function (Blueprint $table) {
      $table
        ->integer('num_of_students')
        ->nullable()
        ->default(0);
    });
  }

  public function down(): void
  {
    Schema::table('result_publications', function (Blueprint $table) {
      $table->dropColumn('status');
    });
  }
};
