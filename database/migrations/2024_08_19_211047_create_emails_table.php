<?php

use App\Enums\EmailStatus;
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
    Schema::create('emails', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->foreignId('institution_id')->constrained('institutions');
      $table
        ->foreignId('sender_user_id')
        ->constrained('users')
        ->cascadeOnDelete();
      $table->string('subject'); // Subject of the email
      $table->text('body'); // Body of the email
      $table->string('type');
      $table->string('status')->default(EmailStatus::Pending->value);
      $table->timestamp('sent_at')->nullable(); // Timestamp when the email was sent
      $table->text('meta')->nullable();
      $table->nullableMorphs('messageable');

      $table->timestamps();
    });

    Schema::create('email_recipients', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->foreignId('institution_id')->constrained('institutions');
      $table->text('recipient_email')->nullable();
      $table->nullableMorphs('recipient');

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
    Schema::dropIfExists('email_recipients');
    Schema::dropIfExists('emails');
  }
}; 
