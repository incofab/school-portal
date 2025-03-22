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
      $table->string('type')->default(EventType::StudentTest->value);
      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained()
        ->cascadeOnDelete();
      $table->string('term')->nullable();
      $table->boolean('for_mid_term')->default(false);
    });
  }

  function removePinPrintTable()
  {
    DB::table('migrations')
      ->where('migration', '2024_08_19_211047_create_emails_table')
      ->orWhere('migration', '2023_06_15_311053_add_print_pin_id_to_pins_table')
      ->delete();

    Schema::table('pins', function (Blueprint $table) {
      $table->dropForeign(['pin_print_id']);
      $table->dropColumn(['pin_print_id']);
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
