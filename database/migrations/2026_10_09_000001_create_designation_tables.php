<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('designations', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->boolean('is_for_students')->default(true);
      $table->string('scope')->default('institution');
      $table->unsignedInteger('max_holders')->default(1);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->softDeletes();
      $table->unique(['institution_id', 'title']);
      $table->index(['institution_id', 'is_active']);
    });

    Schema::create('designation_classification', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('designation_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->timestamps();
      $table->unique(
        ['designation_id', 'classification_id'],
        'designation_classification_unique'
      );
      $table->index(
        ['institution_id', 'classification_id'],
        'designation_inst_classification_index'
      );
    });

    Schema::create('designation_assignments', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('designation_id')
        ->constrained()
        ->restrictOnDelete();
      $table
        ->foreignId('institution_user_id')
        ->constrained()
        ->restrictOnDelete();
      $table
        ->foreignId('classification_id')
        ->nullable()
        ->constrained()
        ->restrictOnDelete();
      $table
        ->foreignId('academic_session_id')
        ->constrained()
        ->restrictOnDelete();
      $table
        ->foreignId('assigned_by_user_id')
        ->constrained('users')
        ->restrictOnDelete();
      $table->timestamp('assigned_at');
      $table->timestamp('ended_at')->nullable();
      $table->text('note')->nullable();
      $table->timestamps();
      $table->index(
        ['designation_id', 'academic_session_id', 'ended_at'],
        'designation_assignment_limit_idx'
      );
      $table->index(
        ['institution_user_id', 'academic_session_id', 'ended_at'],
        'designation_assignment_person_idx'
      );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('designation_assignments');
    Schema::dropIfExists('designation_classification');
    Schema::dropIfExists('designations');
  }
};
