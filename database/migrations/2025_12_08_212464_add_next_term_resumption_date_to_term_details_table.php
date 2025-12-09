<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('term_details', function (Blueprint $table) {
      $table->date('next_term_resumption_date')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('term_details', function (Blueprint $table) {
      $table->dropColumn('next_term_resumption_date');
    });
  }
};
