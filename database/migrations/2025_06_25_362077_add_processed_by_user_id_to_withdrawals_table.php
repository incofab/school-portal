<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('withdrawals', function (Blueprint $table) {
      $table
        ->foreignId('processed_by_user_id')
        ->nullable()
        ->constrained('users')
        ->cascadeOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('withdrawals', function (Blueprint $table) {
      $table->dropForeign(['processed_by_user_id']);
      $table->dropColumn('processed_by_user_id');
    });
  }
};
