<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::table('term_results', function (Blueprint $table) {
      $table
        ->foreignId('result_publication_id')
        ->nullable()
        ->references('id')
        ->on('result_publications')
        ->after('is_activated');
    });
  }

  public function down(): void
  {
    Schema::table('term_results', function (Blueprint $table) {
      $table->dropForeign(['result_publication_id']);
      $table->dropColumn('result_publication_id');
    });
  }
};
