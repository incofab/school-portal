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
  }

  public function down(): void
  {
    Schema::dropIfExists('payroll_summaries');
  }
};
