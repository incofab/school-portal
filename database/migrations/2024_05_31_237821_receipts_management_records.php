<?php

use App\Models\Institution;
use App\Models\ReceiptType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('receipt_types', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->string('title');
      $table->string('descriptions')->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
    });

    // Add receipt_type_id to fees table
    Schema::table('fees', function (Blueprint $table) {
      $table->unsignedBigInteger('receipt_type_id')->nullable();
      $table
        ->foreign('receipt_type_id')
        ->references('id')
        ->on('receipt_types')
        ->cascadeOnDelete();
    });

    Schema::create('receipts', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('receipt_type_id');
      $table->string('reference')->unique();
      $table->string('title')->nullable();
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->unsignedBigInteger('classification_id')->nullable();
      $table->unsignedBigInteger('classification_group_id')->nullable();
      $table->string('term')->nullable();
      $table->float('total_amount', 10, 2);
      $table->dateTime('approved_at')->nullable();
      $table->unsignedBigInteger('approved_by_user_id')->nullable();
      $table->softDeletes();
      $table->timestamps();
      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
      $table
        ->foreign('receipt_type_id')
        ->references('id')
        ->on('receipt_types')
        ->cascadeOnDelete();
      $table
        ->foreign('academic_session_id')
        ->references('id')
        ->on('academic_sessions')
        ->cascadeOnDelete();
      $table
        ->foreign('classification_id')
        ->references('id')
        ->on('classifications')
        ->cascadeOnDelete();
      $table
        ->foreign('classification_group_id')
        ->references('id')
        ->on('classification_groups')
        ->cascadeOnDelete();
      $table
        ->foreign('approved_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
    });

    // Add payment receipts id to fee payments table
    Schema::table('fee_payments', function (Blueprint $table) {
      $table->unsignedBigInteger('receipt_id')->nullable();
      $table->unsignedBigInteger('recorded_by_user_id')->nullable();
      $table
        ->foreign('receipt_id')
        ->references('id')
        ->on('receipts')
        ->cascadeOnDelete();
      $table
        ->foreign('recorded_by_user_id')
        ->references('id')
        ->on('users')
        ->cascadeOnDelete();
    });

    $this->seedReceiptType();
  }

  private function seedReceiptType()
  {
    $types = [['title' => 'Term Receipt']];
    $institutions = Institution::all();
    foreach ($institutions as $key => $institution) {
      foreach ($types as $key => $type) {
        ReceiptType::query()->firstOrCreate(
          array_merge($type, ['institution_id' => $institution->id])
        );
      }
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Remove receipts id from fee payments table
    Schema::table('fee_payments', function (Blueprint $table) {
      $table->dropForeign(['receipt_id']);
      $table->dropForeign(['recorded_by_user_id']);
      $table->dropColumn(['receipt_id', 'recorded_by_user_id']);
    });
    Schema::dropIfExists('receipts');

    // Remove receipt_type_id from fee table
    Schema::table('fees', function (Blueprint $table) {
      $table->dropForeign(['receipt_type_id']);
      $table->dropColumn('receipt_type_id');
    });
    Schema::dropIfExists('receipt_types');
  }
};
