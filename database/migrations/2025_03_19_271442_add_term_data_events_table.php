<?php

use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table
        ->string('type')
        ->index()
        ->default(EventType::StudentTest->value);
      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained()
        ->cascadeOnDelete();
      $table->string('term')->nullable();
      $table->boolean('for_mid_term')->default(false);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table->dropForeign(['academic_session_id']);
      $table->dropColumn([
        'type',
        'academic_session_id',
        'term',
        'for_mid_term'
      ]);
    });
  }
};
