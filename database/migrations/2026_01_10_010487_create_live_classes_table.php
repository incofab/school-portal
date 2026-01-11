<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('live_classes', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('teacher_user_id');
      $table->string('title');
      $table->string('meet_url');
      $table->unsignedBigInteger('liveable_id');
      $table->string('liveable_type');
      $table->timestamp('starts_at')->nullable();
      $table->timestamp('ends_at')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();

      $table
        ->foreign('teacher_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();

      $table->index(['liveable_id', 'liveable_type']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('live_classes');
  }
};
