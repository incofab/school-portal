<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('session_results', function (Blueprint $table) {
      $table->float('total_average')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('session_results', function (Blueprint $table) {
      $table->dropColumn('total_average');
    });
  }
};
