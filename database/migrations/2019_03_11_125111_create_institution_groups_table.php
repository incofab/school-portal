<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('institution_groups', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('user_id')->nullable();
      $table->unsignedBigInteger('partner_user_id')->nullable();
      $table->string('name');
      $table->softDeletes();
      $table->timestamps();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('partner_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
    });

    Schema::table('institutions', function (Blueprint $table) {
      $table->unsignedBigInteger('institution_group_id')->nullable();

      $table
        ->foreign('institution_group_id')
        ->references('id')
        ->on('institution_groups')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('institutions', function (Blueprint $table) {
      $table->dropForeign(['institution_group_id']);
      $table->dropColumn('institution_group_id');
    });
    Schema::dropIfExists('institution_groups');
  }
};
