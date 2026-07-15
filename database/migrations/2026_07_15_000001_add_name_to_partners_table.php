<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('partners', function (Blueprint $table) {
      $table
        ->string('name')
        ->nullable()
        ->after('user_id');
    });

    DB::table('partners')
      ->join('users', 'partners.user_id', '=', 'users.id')
      ->whereNull('partners.name')
      ->update([
        'partners.name' => DB::raw(
          "trim(concat(users.first_name, ' ', users.last_name))"
        )
      ]);
  }

  public function down(): void
  {
    Schema::table('partners', function (Blueprint $table) {
      $table->dropColumn('name');
    });
  }
};
