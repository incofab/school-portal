<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('media', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table
        ->foreignId('institution_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table
        ->foreignId('uploaded_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();
      $table->nullableMorphs('mediable');
      $table->string('collection_name');
      $table->string('disk');
      $table->string('directory')->nullable();
      $table->string('path');
      $table->string('filename');
      $table->string('original_name')->nullable();
      $table->string('extension')->nullable();
      $table->string('mime_type')->nullable();
      $table->unsignedBigInteger('size')->nullable();
      $table->string('kind');
      $table->string('visibility')->default('public');
      $table->string('status');
      $table->string('checksum_sha256', 64)->nullable();
      $table->json('meta')->nullable();
      $table->timestamp('uploaded_at')->nullable();
      $table->timestamp('failed_at')->nullable();
      $table->text('failure_reason')->nullable();
      $table->timestamps();

      $table->index(
        ['mediable_type', 'mediable_id', 'collection_name'],
        'mediable_collection_index'
      );
      $table->index(['institution_id', 'collection_name']);
      $table->index(['disk', 'path']);
      $table->index(['status', 'uploaded_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('media');
  }
};
