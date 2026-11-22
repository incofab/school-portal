<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('classifications', function (Blueprint $table) {
      $table->integer('order')->default(0);
    });

    Schema::table('classification_groups', function (Blueprint $table) {
      $table->integer('order')->default(0);
    });
  }

  public function down(): void
  {
    Schema::table('classification_groups', function (Blueprint $table) {
      $table->dropColumn('order');
    });

    Schema::table('classifications', function (Blueprint $table) {
      $table->dropColumn('order');
    });
  }
};
