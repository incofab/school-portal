<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('bank_accounts', function (Blueprint $table) {
      $table->id();
      $table->morphs('accountable'); // Polymorphic relationship
      $table->string('bank_name');
      $table->string('bank_code')->nullable();
      $table->string('account_name');
      $table->string('account_number');
      $table->softDeletes(); // Adds deleted_at
      $table->timestamps(); // Adds created_at and updated_at
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('bank_accounts');
  }
};
