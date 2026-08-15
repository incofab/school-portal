<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('payouts', function (Blueprint $table) {
      $table->id();
      $table->morphs('payoutable');
      $table->string('purpose')->default('withdrawal');
      $table->string('merchant')->default('monnify');
      $table->string('status')->nullable();
      $table->string('merchant_status')->nullable();
      $table->string('reference')->unique();
      $table->string('batch_reference')->nullable();
      $table->string('provider_reference')->nullable();
      $table->decimal('amount', 12, 2);
      $table->string('currency', 3)->default('NGN');
      $table->boolean('is_processing')->default(false);
      $table->unsignedInteger('attempt_count')->default(0);
      $table->timestamp('attempted_at')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->text('note')->nullable();
      $table->json('provider_response')->nullable();
      $table->timestamps();

      $table->unique(['payoutable_type', 'payoutable_id']);
      $table->index('batch_reference');
      $table->index(['merchant', 'status']);
      $table->index(['purpose', 'status']);
      $table->index(['is_processing', 'attempted_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('payouts');
  }
};
