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
    Schema::table('institutions', function (Blueprint $table) {
      $table->string('subtitle')->nullable();
      $table->string('caption')->nullable();
      $table->string('website')->nullable();
      $table->string('initials')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('institutions', function (Blueprint $table) {
      $table->dropColumn(['caption', 'subtitle', 'website', 'initials']);
    });
  }
};
