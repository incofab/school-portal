<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('transactions', function (Blueprint $table) {
      $table->text('remark')->nullable();
    });

    $this->removePinPrintTable();
  }

  /** pin_prints table is no longer in use, so this will wipe of all data related to it */
  private function removePinPrintTable()
  {
    DB::table('migrations')
      ->where('migration', '2023_06_12_472053_create_pin_prints_table')
      ->orWhere('migration', '2023_06_15_311053_add_print_pin_id_to_pins_table')
      ->delete();

    if (Schema::hasColumn('pins', 'pin_print_id')) {
      Schema::table('pins', function (Blueprint $table) {
        $table->dropForeign(['pin_print_id']);
        $table->dropColumn(['pin_print_id']);
      });
    }
    Schema::dropIfExists('pin_prints');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('transactions', function (Blueprint $table) {
      $table->dropColumn(['remark']);
    });
  }
};
