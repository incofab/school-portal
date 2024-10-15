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
        $table->string('lga')->nullable();
        $table->string('state')->nullable();
        $table->string('intended_class_of_admission')->nullable();
        $table->string('fathers_phone', 20)->nullable();
        $table->string('fathers_email')->nullable();
        $table->string('fathers_residential_address')->nullable();
        $table->string('fathers_office_address')->nullable();
        $table->string('mothers_phone', 20)->nullable();
        $table->string('mothers_email')->nullable();
        $table->string('mothers_residential_address')->nullable();
        $table->string('mothers_office_address')->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('admission_applications', function (Blueprint $table) {
        $table->dropColumn([
            'lga',
            'state',
            'intended_class_of_admission',
            'fathers_phone',
            'fathers_email',
            'fathers_residential_address',
            'fathers_office_address',
            'mothers_phone',
            'mothers_email',
            'mothers_residential_address',
            'mothers_office_address'
        ]);
    });
    }
};