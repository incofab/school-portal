<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('pins', function (Blueprint $table) {
      $table->unsignedBigInteger('pin_print_id')->nullable();

      $table
        ->foreign('pin_print_id')
        ->references('id')
        ->on('pin_prints')
        ->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('pins', function (Blueprint $table) {
      $table->dropForeign(['pin_print_id']);
      $table->dropColumn(['pin_print_id']);
    });
  }
};
