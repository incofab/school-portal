<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\SettlementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('payment_references', function (Blueprint $table) {
      $table
        ->timestamp('settled_at')
        ->nullable()
        ->after('processed_at')
        ->index();
    });

    DB::table('payment_references')
      ->where('status', PaymentStatus::Confirmed->value)
      ->whereIn(
        'merchant',
        array_map(fn($enum) => $enum->value, PaymentMerchantType::settleable())
      )
      ->whereIn(
        'purpose',
        array_map(fn($enum) => $enum->value, PaymentPurpose::settleable())
      )
      ->whereNull('settled_at')
      ->update(['settled_at' => now()]);

    Schema::create('settlements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('institution_id')->constrained('institutions');
      $table
        ->foreignId('withdrawal_id')
        ->nullable()
        ->constrained('withdrawals')
        ->nullOnDelete();
      $table->decimal('amount', 12, 2);
      $table->string('status')->default(SettlementStatus::Completed->value);
      $table->timestamp('processed_at')->nullable();
      $table->timestamps();

      $table->index('institution_id');
      $table->index('status');
    });

    Schema::create('settlement_payments', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('settlement_id')
        ->constrained('settlements')
        ->cascadeOnDelete();
      $table
        ->foreignId('payment_reference_id')
        ->constrained('payment_references')
        ->cascadeOnDelete();
      $table->decimal('amount', 12, 2);
      $table->timestamps();

      $table->unique('payment_reference_id');
      $table->unique(
        ['settlement_id', 'payment_reference_id'],
        'settlement_payment_unique'
      );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('settlement_payments');
    Schema::dropIfExists('settlements');

    Schema::table('payment_references', function (Blueprint $table) {
      $table->dropIndex(['settled_at']);
      $table->dropColumn(['settled_at']);
    });
  }
};
