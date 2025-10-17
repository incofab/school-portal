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
    Schema::create('classifiables', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('classification_id')
        ->constrained()
        ->cascadeOnDelete();

      // The Polymorphic columns
      $table->morphs('classifiable');

      // Optional: Primary key for the pivot table, and a unique constraint
      // $table->primary(['classification_id', 'classifiable_id', 'classifiable_type'], 'classifiable_primary');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('classifiables');
  }
};
