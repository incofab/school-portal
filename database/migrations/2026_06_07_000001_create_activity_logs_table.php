<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('activity_logs', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table
        ->foreignId('institution_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table
        ->foreignId('institution_group_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table->nullableMorphs('actor');
      $table->string('actor_name')->nullable();
      $table->string('actor_role')->nullable();
      $table->string('actor_guard')->nullable();
      $table->string('action');
      $table->string('category');
      $table->string('event');
      $table->nullableMorphs('subject');
      $table->string('subject_name')->nullable();
      $table->text('description')->nullable();
      $table->json('properties')->nullable();
      $table->json('old_values')->nullable();
      $table->json('new_values')->nullable();
      $table->string('ip_address')->nullable();
      $table->text('user_agent')->nullable();
      $table->string('route_name')->nullable();
      $table->text('url')->nullable();
      $table->string('method')->nullable();
      $table->string('request_id')->nullable();
      $table->nullableMorphs('impersonator');
      $table->string('severity')->default('info');
      $table->timestamps();

      $table->index(['institution_id', 'created_at']);
      $table->index(['institution_group_id', 'created_at']);
      $table->index(['category', 'event']);
      $table->index(['action', 'severity']);
      $table->index('request_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('activity_logs');
  }
};
