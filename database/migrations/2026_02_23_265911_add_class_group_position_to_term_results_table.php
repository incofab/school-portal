<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('class_group_result_info', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('classification_group_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('academic_session_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('term')->nullable();
      $table->boolean('for_mid_term')->default(false);
      $table->integer('num_of_students')->default(0);
      $table->float('total_score')->default(0);
      $table->float('max_obtainable_score')->default(0);
      $table->float('max_score')->default(0);
      $table->float('min_score')->default(0);
      $table->float('average')->default(0);

      $table->timestamps();
    });

    Schema::table('term_results', function (Blueprint $table) {
      $table->unsignedInteger('class_group_position')->nullable();
    });

    Schema::table('classification_groups', function (Blueprint $table) {
      $table->boolean('show_class_group_position')->default(true);
    });
  }

  public function down(): void
  {
    Schema::table('classification_groups', function (Blueprint $table) {
      $table->dropColumn(['show_class_group_position']);
    });

    Schema::table('term_results', function (Blueprint $table) {
      $table->dropColumn(['class_group_position']);
    });

    Schema::dropIfExists('class_group_result_info');
  }
};
