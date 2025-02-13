<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_of_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('week_number');
            $table->text('learning_objectives');
            $table->text('resources');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_of_works');
    }
};