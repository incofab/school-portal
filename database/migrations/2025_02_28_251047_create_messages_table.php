<?php

use App\Enums\MessageStatus;
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
    Schema::dropIfExists('email_recipients');
    Schema::dropIfExists('emails');
    DB::table('migrations')
      ->where('migration', '2024_08_19_211047_create_emails_table')
      ->delete();

    Schema::create('messages', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table
        ->foreignId('institution_id')
        ->constrained('institutions')
        ->cascadeOnDelete();
      $table
        ->foreignId('sender_user_id')
        ->constrained('users')
        ->cascadeOnDelete();
      $table->string('subject')->nullable(); // Subject of the email
      $table->text('body'); // Body of the email
      $table->string('channel'); // sms or email
      $table->string('recipient_category');
      $table->string('status')->default(MessageStatus::Pending->value);
      $table->timestamp('sent_at')->nullable(); // Timestamp when the email was sent
      $table->text('meta')->nullable();
      $table->nullableMorphs('messageable');

      $table->timestamps();
    });

    Schema::create('message_recipients', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table
        ->foreignId('institution_id')
        ->constrained('institutions')
        ->cascadeOnDelete();
      $table
        ->foreignId('message_id')
        ->constrained('messages')
        ->cascadeOnDelete();
      $table->text('recipient_contact')->nullable();
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
    Schema::dropIfExists('message_recipients');
    Schema::dropIfExists('messages');
  }
};
