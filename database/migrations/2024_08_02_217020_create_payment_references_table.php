<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    // 'id', 'user_id', 'reference_id', 'reference','currency','method', 'type','amount','status','access_code','url','meta_data','transaction_date','charges'
    Schema::create('payment_references', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->foreignId('institution_id')->constrained('institutions');
      $table
        ->foreignId('user_id')
        ->nullable()
        ->constrained('users'); // The user making the payment or inst admin user
      $table->morphs('payable'); // The entity making the payment
      $table->string('reference')->unique();
      $table->string('merchant')->default(PaymentMerchantType::Paystack->value);
      $table->string('method')->nullable();
      $table->float('amount', 30, 2);
      $table->string('status')->default(PaymentStatus::Pending->value);
      $table->string('purpose');
      $table->string('access_code')->nullable(true);
      $table->float('charges', 30, 2)->default(0);
      $table->longText('meta')->nullable(true);
      $table->longText('payload')->nullable(true);
      $table->string('redirect_url')->nullable(true);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('payment_references');
  }
};
