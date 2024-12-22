<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_sub_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_topic_id')->constrained('note_topics')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('status'); //'published' or 'draft'
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_topics');
    }
};