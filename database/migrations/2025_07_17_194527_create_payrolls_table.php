<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('payroll_summaries', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->decimal('amount', 12, 2)->default(0);
      $table->decimal('total_tax', 12, 2)->default(0);
      $table->decimal('total_deduction', 12, 2)->default(0);
      $table->decimal('total_bonuses', 12, 2)->default(0);
      $table->dateTime('evaluated_at')->nullable();
      $table->string('month');
      $table->year('year');
      $table->timestamps();

      $table->index(['month', 'year']);
      $table->unique(['institution_id', 'month', 'year']);
    });

    Schema::create('payroll', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_user_id')
        ->constrained()
        ->cascadeOnDelete();

      $table->decimal('tax', 10, 2)->default(0);
      $table->decimal('total_deductions', 10, 2)->default(0);
      $table->decimal('total_bonuses', 10, 2)->default(0);
      $table->decimal('gross_salary', 10, 2)->default(0);
      $table->decimal('net_salary', 10, 2);
      $table
        ->foreignId('payroll_summary_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->json('meta')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('payroll_adjustment_types', function (Blueprint $table) {
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

      $table->index('parent_id');
      $table
        ->foreign('parent_id')
        ->references('id')
        ->on('payroll_adjustment_types')
        ->cascadeOnDelete();
    });

    Schema::create('payroll_adjustments', function (Blueprint $table) {
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
        ->foreignId('payroll_adjustment_type_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('payroll_summary_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->decimal('amount', 10, 2);
      $table->string('reference');
      $table->text('description')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('payroll_adjustments');

    Schema::dropIfExists('payroll_adjustment_types');

    Schema::dropIfExists('payroll');

    Schema::dropIfExists('payroll_summaries');
  }
};
