<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('admission_applications', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('other_names')->nullable();
      $table->string('fathers_name')->nullable();
      $table->string('mothers_name')->nullable();
      $table->string('fathers_occupation')->nullable();
      $table->string('mothers_occupation')->nullable();
      $table->string('phone')->nullable();
      $table->string('guardian_phone')->nullable();
      $table->string('photo')->nullable();
      $table->string('email')->nullable();
      $table->string('gender')->nullable();
      $table->string('address')->nullable();
      $table->string('previous_school_attended')->nullable();
      $table->string('dob')->nullable();
      $table->string('nationality')->nullable();
      $table->string('religion')->nullable();
      $table->string('reference')->unique();

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
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('admission_applications');
  }
};
