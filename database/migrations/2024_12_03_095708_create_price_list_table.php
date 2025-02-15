<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('institution_group_id')->constrained('institution_groups')->cascadeOnDelete();
            $table->string('payment_structure');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('result_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('institution_group_id')->constrained('institution_groups')->cascadeOnDelete();
            $table->string('term');
            $table->integer('academic_session_id');
            $table->integer('num_of_results');
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('payment_structure')->comment('The payment structure as at the time of publishing this result');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('result_publications');
    }
};