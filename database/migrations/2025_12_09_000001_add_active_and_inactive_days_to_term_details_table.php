<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('term_details', function (Blueprint $table) {
      $table->json('inactive_weekdays')->nullable()->after('for_mid_term');
      $table->json('special_active_days')->nullable()->after('inactive_weekdays');
      $table->json('inactive_days')->nullable()->after('special_active_days');
    });
  }

  public function down(): void
  {
    Schema::table('term_details', function (Blueprint $table) {
      $table->dropColumn([
        'inactive_weekdays',
        'special_active_days',
        'inactive_days'
      ]);
    });
  }
};
