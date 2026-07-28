<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('classification_groups', function (Blueprint $table) {
      $table
        ->string('head_of_school_title')
        ->default('Principal')
        ->after('title');
      $table
        ->string('head_of_class_title')
        ->default('Form Teacher')
        ->after('head_of_school_title');
      $table
        ->string('student_title')
        ->default('Students')
        ->after('head_of_class_title');
    });
  }

  public function down(): void
  {
    Schema::table('classification_groups', function (Blueprint $table) {
      $table->dropColumn([
        'head_of_school_title',
        'head_of_class_title',
        'student_title'
      ]);
    });
  }
};
