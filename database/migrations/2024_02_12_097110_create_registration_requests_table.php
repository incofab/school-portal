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
    Schema::create('registration_requests', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('partner_user_id')->nullable();
      $table->string('reference')->unique();
      $table->longText('data');
      $table->dateTime('institution_registered_at')->nullable();
      $table->dateTime('institution_group_registered_at')->nullable();
      $table->softDeletes();
      $table->timestamps();
      $table
        ->foreign('partner_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('registration_requests');
  }
};
