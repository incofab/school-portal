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
    Schema::create('expense_categories', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('expenses', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->decimal('amount', 10, 2);
      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained()
        ->cascadeOnDelete();
      $table->string('term')->nullable();
      $table->date('expense_date');
      $table
        ->foreignId('expense_category_id')
        ->constrained('expense_categories')
        ->cascadeOnDelete();
      $table
        ->foreignId('created_by')
        ->constrained('institution_users')
        ->cascadeOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('expenses');

    Schema::dropIfExists('expense_categories');
  }
};
