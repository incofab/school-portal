<?php

use App\Enums\AdmissionStatusType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('admission_applications', function (Blueprint $table) {
      $table
        ->enum('admission_status', ['pending', 'admitted', 'declined'])
        ->default(AdmissionStatusType::Pending->value);
    });
  }

  public function down(): void
  {
    Schema::table('admission_applications', function (Blueprint $table) {
      $table->dropColumn('admission_status');
    });
  }
};
