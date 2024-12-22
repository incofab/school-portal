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
        Schema::table('institution_groups', function (Blueprint $table) {
            // $table->decimal('wallet_balance', 15, 2)->after('name')->default(0.00); // Add the column with a default value
            $table->decimal('credit_wallet', 15, 2)->after('name')->default(0.00);
            $table->decimal('debt_wallet', 15, 2)->after('credit_wallet')->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_groups', function (Blueprint $table) {
            // $table->dropColumn('wallet_balance');
            $table->dropColumn('credit_wallet');
            $table->dropColumn('debt_wallet');
        });
    }
};