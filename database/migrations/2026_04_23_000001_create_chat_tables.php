<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('chat_threads', function (Blueprint $table) {
      $table->id();
      $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
      $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->string('type');
      $table->string('target_role')->nullable();
      $table->string('last_message_preview', 160)->nullable();
      $table->timestamp('last_message_at')->nullable();
      $table->timestamps();

      $table->index(['institution_id', 'type']);
      $table->index(['institution_id', 'requester_user_id']);
      $table->index(['institution_id', 'target_user_id']);
      $table->index(['institution_id', 'target_role']);
    });

    Schema::create('chat_messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
      $table->foreignId('chat_thread_id')->constrained('chat_threads')->cascadeOnDelete();
      $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
      $table->text('body');
      $table->timestamps();

      $table->index(['chat_thread_id', 'id']);
      $table->index(['institution_id', 'sender_user_id']);
    });

    Schema::create('chat_thread_reads', function (Blueprint $table) {
      $table->id();
      $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
      $table->foreignId('chat_thread_id')->constrained('chat_threads')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('last_read_chat_message_id')
        ->nullable()
        ->constrained('chat_messages')
        ->nullOnDelete();
      $table->timestamp('read_at')->nullable();
      $table->timestamps();

      $table->unique(['chat_thread_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('chat_thread_reads');
    Schema::dropIfExists('chat_messages');
    Schema::dropIfExists('chat_threads');
  }
};
