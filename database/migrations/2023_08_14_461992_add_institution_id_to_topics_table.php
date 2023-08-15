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
    Schema::table('topics', function (Blueprint $table) {
      $table->unsignedBigInteger('institution_id')->nullable();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('topics', function (Blueprint $table) {
      $table->dropForeign(['institution_id']);
      $table->dropColumn('institution_id');
    });
  }
};
