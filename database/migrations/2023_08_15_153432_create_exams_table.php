<?php

use App\Enums\ExamStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('exams', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('event_id')->nullable(true);
      $table->unsignedBigInteger('student_id')->nullable(true);
      $table->string('external_reference')->nullable();
      $table
        ->string('exam_no')
        ->unique()
        ->index();

      // $table->string('duration', 10);
      $table->float('time_remaining');
      $table->dateTime('start_time')->nullable(true);
      $table->dateTime('pause_time')->nullable(true);
      $table->dateTime('end_time')->nullable(true);
      $table->integer('score', false, true)->nullable(true);
      $table->integer('num_of_questions', false, true)->nullable(true);
      $table->string('status')->default(ExamStatus::Pending->value);
      $table->text('attempts')->nullable();

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->onUpdate('cascade')
        ->onDelete('cascade');
      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
        ->cascadeOnDelete();
      $table
        ->foreign('student_id')
        ->references('id')
        ->on('students')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('exams');
  }
};
