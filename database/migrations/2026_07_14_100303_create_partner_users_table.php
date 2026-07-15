<?php

use App\Actions\RecordUsers\BackfillPartnerUsers;
use App\Enums\PartnerUserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('partner_users', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('partner_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('user_id')
        ->unique()
        ->constrained()
        ->cascadeOnDelete();
      $table->enum('role', PartnerUserRole::values());
      $table->timestamps();

      $table->unique(['partner_id', 'user_id']);
    });

    BackfillPartnerUsers::run();
  }

  public function down(): void
  {
    Schema::dropIfExists('partner_users');
  }
};
