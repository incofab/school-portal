<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_groups', function (Blueprint $table) {
            $table->decimal('loan_limit', 15, 2)->default(0)->after('debt_wallet');
        });
    }

    public function down(): void
    {
        Schema::table('institution_groups', function (Blueprint $table) {
            $table->dropColumn('loan_limit');
        });
    }
};