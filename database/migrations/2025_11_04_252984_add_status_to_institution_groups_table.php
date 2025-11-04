<?php

use App\Enums\InstitutionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('institution_groups', function (Blueprint $table) {
      $table
        ->string('status')
        ->nullable()
        ->default(InstitutionStatus::Active->value);
    });
  }

  public function down(): void
  {
    Schema::table('institution_groups', function (Blueprint $table) {
      $table->dropColumn('status');
    });
  }
};
