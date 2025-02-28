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
    Schema::create('school_notifications', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained('institutions')
        ->cascadeOnDelete();
      $table->string('purpose');
      $table->text('description')->nullable();
      $table
        ->foreignId('sender_user_id')
        ->constrained('users')
        ->cascadeOnDelete();
      $table->string('receiver_type')->nullable();
      $table->string('reference');
      $table->json('receiver_ids')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('school_notifications');
  }
};
