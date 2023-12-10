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
    Schema::create('result_comment_templates', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->string('type')->nullable();
      $table->text('comment')->nullable();
      $table->text('grade')->nullable();
      $table->text('grade_label')->nullable();
      $table->float('min');
      $table->float('max');
      $table->text('description')->nullable();
      $table->timestamps();
      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('result_comment_templates');
  }
};
