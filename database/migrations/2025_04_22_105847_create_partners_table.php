<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('partners', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('user_id')
        ->constrained()
        ->onDelete('cascade');
      $table->decimal('commission', 10, 2);
      $table
        ->foreignId('referral_user_id')
        ->nullable()
        ->constrained('users')
        ->onDelete('set null');
      $table->decimal('referral_commission', 10, 2)->default(0.0);
      $table->decimal('wallet', 10, 2)->default(0.0);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('partners');
  }
};
