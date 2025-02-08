<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('institution_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('classification_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_topic_id')->nullable()->constrained('topics')->onDelete('cascade');
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['institution_group_id']);
            $table->dropForeign(['classification_group_id']);
            $table->dropForeign(['parent_topic_id']);
            $table->dropColumn(['institution_group_id', 'classification_group_id', 'parent_topic_id', 'deleted_at']);
        });
    }
};