<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    $driver = DB::getDriverName();

    if ($driver === 'mysql') {
      DB::statement(
        'DELETE duplicate_user_transactions FROM user_transactions duplicate_user_transactions INNER JOIN user_transactions kept_user_transactions ON duplicate_user_transactions.reference = kept_user_transactions.reference AND duplicate_user_transactions.id > kept_user_transactions.id'
      );
    } else {
      DB::statement(
        'DELETE FROM user_transactions WHERE id NOT IN (SELECT MIN(id) FROM user_transactions GROUP BY reference)'
      );
    }

    Schema::table('user_transactions', function (Blueprint $table) {
      if (Schema::hasIndex('user_transactions', ['reference'], 'index')) {
        $table->dropIndex(['reference']);
      }
      $table->unique('reference');
    });
  }

  public function down(): void
  {
    Schema::table('user_transactions', function (Blueprint $table) {
      $table->dropUnique(['reference']);
      $table->index('reference');
    });
  }
};
