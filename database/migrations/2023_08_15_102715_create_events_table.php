<?php

use App\Enums\EventStatus;
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
    Schema::create('events', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('institution_id');
      $table->string('title');
      $table->string('description')->nullable(true);
      $table->float('duration');
      $table->string('status')->default(EventStatus::Active->value);
      $table->integer('num_of_activations', false, true)->default(0);
      $table->dateTime('starts_at')->nullable();
      $table->unsignedInteger('num_of_subjects');

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->onUpdate('cascade')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('events');
  }
};
