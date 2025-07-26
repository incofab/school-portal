<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('salary_types', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->string('type'); //TransactionType enum
      $table->unsignedBigInteger('parent_id')->nullable();
      $table->decimal('percentage', 5, 2)->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table
        ->foreign('parent_id')
        ->references('id')
        ->on('salary_types')
        ->cascadeOnDelete();
    });

    Schema::create('salaries', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_user_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('salary_type_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->decimal('amount', 10, 2);
      $table->text('description')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('salaries');

    Schema::dropIfExists('salary_types');
  }
};
