<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('associations', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->text('description')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
    Schema::create('user_associations', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_user_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('association_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('user_associations');
    Schema::dropIfExists('associations');
  }
};
