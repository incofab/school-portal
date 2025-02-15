<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('timetables', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained('institutions')
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_id')
        ->constrained('classifications')
        ->cascadeOnDelete();
      $table->integer('day')->unsigned(); //== days field (0-6)  - unsigned, because days can be 0 to 6
      $table->time('start_time');
      $table->time('end_time');
      $table->morphs('actionable'); //== creates 'actionable_id' and 'actionable_type' columns
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('timetables');
  }
};