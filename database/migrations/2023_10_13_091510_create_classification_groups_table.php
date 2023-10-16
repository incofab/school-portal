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
        Schema::create('classification_groups', function (Blueprint $table) {
          $table->bigIncrements('id');
          $table->unsignedBigInteger('institution_id');
          $table->string('title');          
          $table->timestamps(); 
          $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classification_groups');
    }
};