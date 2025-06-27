<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('payment_references', function (Blueprint $table) {
      $table->dateTime('processed_at')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('payment_references', function (Blueprint $table) {
      $table->dropColumn(['processed_at']);
    });
  }
};
