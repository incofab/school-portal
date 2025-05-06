<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('partner_registration_requests', function (
      Blueprint $table
    ) {
      $table->bigIncrements('id');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('other_names')->nullable();
      $table->string('phone');
      $table->string('email');
      $table
        ->foreignId('referral_id')
        ->nullable()
        ->constrained('partners')
        ->nullOnDelete();
      $table->string('username');
      $table->string('gender');
      $table->string('password');
      $table->string('reference');
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('partner_registration_requests');
  }
};
