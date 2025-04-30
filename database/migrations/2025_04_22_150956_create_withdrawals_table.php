<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('withdrawals', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('bank_account_id')
        ->constrained('bank_accounts')
        ->onDelete('cascade');

      $table->morphs('withdrawable'); // Polymorphic relation
      $table->decimal('amount', 12, 2);
      $table->string('status');
      $table->string('reference');
      $table->text('remark')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('withdrawals');
  }
};
