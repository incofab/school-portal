<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  function up()
  {
    // Admission Forms Table
    Schema::create('admission_forms', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->mediumText('description')->nullable();
      $table->decimal('price', 10, 2)->default(0.0);
      $table->boolean('is_published')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });

    // Admission Form Purchases Table
    Schema::create('admission_form_purchases', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('admission_form_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('reference')->unique();
      $table->timestamps();
      $table->softDeletes();
    });

    // Add admission form ID to the applications table
    Schema::table('admission_applications', function (Blueprint $table) {
      $table
        ->string('application_no')
        ->nullable()
        ->unique();
      $table
        ->foreignId('admission_form_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table
        ->foreignId('admission_form_purchase_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
    });

    // Add payment morph to payment references table
    Schema::table('payment_references', function (Blueprint $table) {
      $table->nullableMorphs('paymentable');
    });
  }

  public function down()
  {
    Schema::table('payment_references', function (Blueprint $table) {
      $table->dropMorphs('paymentable');
    });
    Schema::table('admission_applications', function (Blueprint $table) {
      $table->dropForeign(['admission_form_purchase_id']);
      $table->dropForeign(['admission_form_id']);
      $table->dropColumn([
        'application_no',
        'admission_form_purchase_id',
        'admission_form_id'
      ]);
    });
    Schema::dropIfExists('admission_form_purchases');
    Schema::dropIfExists('admission_forms');
  }
};
