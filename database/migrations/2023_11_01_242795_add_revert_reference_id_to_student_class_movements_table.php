<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('student_class_movements', function (Blueprint $table) {
      $table
        ->unsignedBigInteger('source_classification_id')
        ->nullable()
        ->change();
      $table->string('reason')->nullable();
      $table->unsignedBigInteger('revert_reference_id')->nullable();
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->string('term')->nullable();

      $table
        ->foreign('revert_reference_id')
        ->references('id')
        ->on('student_class_movements')
        ->cascadeOnDelete();

      $table
        ->foreign('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->cascadeOnDelete();
    });
  }

  public function down()
  {
    Schema::table('student_class_movements', function (Blueprint $table) {
      $table
        ->unsignedBigInteger('source_classification_id')
        ->nullable()
        ->change(); // Changing it back to non-null throws an exception
      $table->dropForeign(['revert_reference_id']);
      $table->dropForeign(['academic_session_id']);
      $table->dropColumn([
        'revert_reference_id',
        'reason',
        'academic_session_id',
        'term'
      ]);
    });
  }
};
