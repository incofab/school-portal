<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('academic_sessions', function (Blueprint $table) {
      $table
        ->boolean('is_active')
        ->default(false)
        ->after('order_index')
        ->index();
    });

    $latestAcademicSessionId = DB::table('academic_sessions')
      ->whereNull('deleted_at')
      ->orderByDesc('id')
      ->value('id');

    if ($latestAcademicSessionId) {
      DB::table('academic_sessions')
        ->where('id', $latestAcademicSessionId)
        ->update(['is_active' => true]);
    }
  }

  public function down(): void
  {
    Schema::table('academic_sessions', function (Blueprint $table) {
      $table->dropIndex(['is_active']);
      $table->dropColumn('is_active');
    });
  }
};
