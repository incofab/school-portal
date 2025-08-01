<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('bank_accounts', function (Blueprint $table) {
      $table->boolean('is_primary')->default(false);
    });

    Schema::create('reserved_accounts', function (Blueprint $table) {
      $table->id();

      $table->morphs('reservable'); // Polymorphic relationship
      $table->string('merchant')->nullable();
      $table->string('bank_name');
      $table->string('bank_code')->nullable();
      $table->string('account_name');
      $table->string('account_number');
      $table->string('status')->nullable();
      $table
        ->string('reference')
        ->nullable()
        ->index();

      $table->softDeletes();
      $table->timestamps();
    });

    Schema::create('banks', function (Blueprint $table) {
      $table->id();
      $table->string('country_code')->nullable();
      $table->string('bank_name');
      $table->string('bank_code')->nullable();
      $table->string('status')->nullable();
      $table->boolean('support_account_verification')->default(true);

      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('banks');

    Schema::dropIfExists('reserved_accounts');

    Schema::table('bank_accounts', function (Blueprint $table) {
      $table->dropColumn('is_primary');
    });
  }
};
