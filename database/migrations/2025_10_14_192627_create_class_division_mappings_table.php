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
    Schema::create('class_division_mappings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('class_division_id')->constrained('class_divisions');
      $table->morphs('mappable');
      // $table->foreignId('classification_id')->constrained('classifications');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('class_division_mappings');
  }
};
