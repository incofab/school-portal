<?php

use App\Enums\InstitutionUserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('institution_users', function (Blueprint $table) {
      $table->string('status')->default(InstitutionUserStatus::Active->value);
    });
  }

  public function down(): void
  {
    Schema::table('institution_users', function (Blueprint $table) {
      $table->dropColumn(['status']);
    });
  }
};
