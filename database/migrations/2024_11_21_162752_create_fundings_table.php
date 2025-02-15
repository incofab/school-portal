<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funded_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('institution_group_id')->constrained()->onDelete('cascade');
            $table->string('wallet')->comment("This can be either CREDIT or DEBT and refers to the institution_group's wallet (Credit_Wallet / Debt_Wallet) being affected by this transaction");
            $table->decimal('amount', 15, 2);
            $table->decimal('previous_balance', 15, 2);
            $table->decimal('new_balance', 15, 2);
            $table->text('remark')->nullable();
            $table->decimal('charge')->default(0.00);
            $table->string('reference')->unique();
            $table->nullableMorphs('fundable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundings');
    }
};
