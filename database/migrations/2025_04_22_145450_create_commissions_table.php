<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('commissions', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_group_id')
        ->constrained('institution_groups')
        ->onDelete('cascade');
      $table
        ->foreignId('partner_id')
        ->constrained('partners')
        ->onDelete('cascade');
      $table->decimal('commission', 10, 2);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('commissions');
  }
};
