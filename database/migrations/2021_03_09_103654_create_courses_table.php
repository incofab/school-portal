<?php

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
    Schema::create('courses', function (Blueprint $table) {
      $table->bigIncrements('id');

      $table->unsignedBigInteger('institution_id')->nullable(true);

      $table->string('title')->nullable(true);
      $table->string('code');
      $table->string('category')->nullable(true);
      $table->text('description')->nullable(true);

      $table->boolean('is_file_content_uploaded')->default(false);

      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('courses');
  }
};
