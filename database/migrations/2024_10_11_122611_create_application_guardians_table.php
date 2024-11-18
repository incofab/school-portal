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
        Schema::create('application_guardians', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedBigInteger('admission_application_id')->nullable();
            $table->timestamps();

            $table->foreign('admission_application_id')->references('id')->on('admission_applications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_guardians');
    }
};