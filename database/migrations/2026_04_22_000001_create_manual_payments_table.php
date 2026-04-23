<?php

use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_payments', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('institution_id')
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table
                ->foreignId('bank_account_id')
                ->nullable()
                ->constrained('bank_accounts')
                ->nullOnDelete();
            $table->nullableMorphs('payable');
            $table->nullableMorphs('paymentable');
            $table->string('reference')->unique();
            $table->float('amount', 30, 2);
            $table->string('purpose');
            $table->string('method')->nullable();
            $table->string('status')->default(PaymentStatus::Pending->value);
            $table->string('depositor_name')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->longText('meta')->nullable();
            $table->longText('payload')->nullable();
            $table
                ->foreignId('confirmed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table
                ->foreignId('rejected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_payments');
    }
};
