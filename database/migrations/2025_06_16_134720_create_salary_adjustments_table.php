<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adjustment_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('month');
            $table->year('year');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['month', 'year']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('salary_adjustments');
    }
};
