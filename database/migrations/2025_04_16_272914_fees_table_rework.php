<?php

use App\Actions\Dummy\ReworkFees;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    if (!ReworkFees::make()->isBackedUp()) {
      dd('You need to backup first');
      return;
    }

    Schema::dropIfExists('fee_payment_tracks');
    Schema::dropIfExists('fee_payments');
    Schema::dropIfExists('receipts');
    Schema::dropIfExists('fee_categories');
    Schema::dropIfExists('fees');
    Schema::dropIfExists('receipt_types');

    deleteMigrationEntry([
      '2024_05_31_237821_receipts_management_records',
      '2023_06_17_503158_create_fee_payment_tracks_table',
      '2023_06_17_489391_create_fee_payments_table',
      '2023_06_17_472053_create_fees_table',
      '2025_03_19_261901_add_paymentable_to_fee_payments_table'
    ]);

    Schema::create('fees', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->float('amount', 10, 2);
      $table->string('payment_interval');
      $table->string('term')->nullable();

      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained()
        ->cascadeOnDelete();
      $table->json('fee_items');
      $table->softDeletes();
      $table->timestamps();
    });

    Schema::create('fee_categories', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('fee_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->morphs('feeable');
      $table->timestamps();
    });

    Schema::create('receipts', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('fee_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->float('amount', 10, 2);
      $table->float('amount_paid', 10, 2);
      $table->float('amount_remaining', 10, 2);
      $table->string('status')->nullable();
      $table->string('term')->nullable();
      $table
        ->foreignId('academic_session_id')
        ->nullable()
        ->constrained()
        ->cascadeOnDelete();
      $table->softDeletes();
      $table->timestamps();
    });

    Schema::create('fee_payments', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('fee_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('receipt_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->float('amount', 10, 2);
      $table
        ->foreignId('confirmed_by_user_id')
        ->nullable()
        ->constrained('users')
        ->cascadeOnDelete();
      $table->string('method')->nullable();
      $table->string('reference')->unique();
      $table->nullableMorphs('payable'); // The entity making the payment
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('fee_payments');
    Schema::dropIfExists('receipts');
    Schema::dropIfExists('fee_categories');
    Schema::dropIfExists('fees');
  }
};
