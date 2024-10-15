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
        Schema::table('admission_applications', function (Blueprint $table) {
            // Remove specified columns
            $table->dropColumn([
                'fathers_name',
                'mothers_name',
                'fathers_occupation',
                'mothers_occupation',
                'guardian_phone',
                'fathers_phone',
                'fathers_email',
                'fathers_residential_address',
                'fathers_office_address',
                'mothers_phone',
                'mothers_email',
                'mothers_residential_address',
                'mothers_office_address',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            // Re-add the removed columns
            $table->string('fathers_name')->nullable();
            $table->string('mothers_name')->nullable();
            $table->string('fathers_occupation')->nullable();
            $table->string('mothers_occupation')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('fathers_phone')->nullable();
            $table->string('fathers_email')->nullable();
            $table->string('fathers_residential_address')->nullable();
            $table->string('fathers_office_address')->nullable();
            $table->string('mothers_phone')->nullable();
            $table->string('mothers_email')->nullable();
            $table->string('mothers_residential_address')->nullable();
            $table->string('mothers_office_address')->nullable();
        });
    }
};
