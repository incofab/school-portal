<?php

use App\Enums\LibrarySourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('libraries', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_user_id')
        ->nullable()
        ->constrained('institution_users')
        ->nullOnDelete();
      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained('academic_sessions')
        ->nullOnDelete();
      $table->string('term')->nullable();
      $table->string('title');
      $table
        ->foreignId('course_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table->string('material_type')->default('document');
      $table->string('source_type')->default(LibrarySourceType::Upload->value);
      $table->text('description')->nullable();
      $table->boolean('is_public')->default(true);
      $table->boolean('is_published')->default(true);
      $table->string('external_url')->nullable();
      $table->string('file_url')->nullable();
      $table->string('file_path')->nullable();
      $table->string('file_name')->nullable();
      $table->string('file_mime_type')->nullable();
      $table->string('file_extension')->nullable();
      $table->unsignedBigInteger('file_size')->nullable();
      $table->timestamp('published_at')->nullable();
      $table->timestamps();

      $table->index(['institution_id', 'is_public', 'is_published']);
      $table->index(['institution_id', 'material_type']);
      $table->index(['institution_id', 'source_type']);
      $table->index(['institution_id', 'course_id']);
    });

    Schema::create('library_classifications', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('library_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->timestamps();

      $table->unique(
        ['library_id', 'classification_id'],
        'library_classification_unique'
      );
      $table->index(['institution_id', 'classification_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('library_classifications');
    Schema::dropIfExists('libraries');
  }
};
