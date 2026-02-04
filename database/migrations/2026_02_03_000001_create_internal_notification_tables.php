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
    Schema::create('internal_notifications', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->nullable()
        ->constrained('institutions')
        ->cascadeOnDelete();
      $table->morphs('sender');
      $table->string('type')->nullable();
      $table->string('title');
      $table->text('body')->nullable();
      $table->string('action_url')->nullable();
      $table->json('data')->nullable();
      $table->timestamps();

      $table->index(['institution_id', 'created_at']);
    });

    Schema::create('internal_notification_targets', function (
      Blueprint $table
    ) {
      $table->id();
      $table
        ->foreignId('internal_notification_id')
        ->constrained('internal_notifications')
        ->cascadeOnDelete();
      $table->morphs('notifiable', 'notif_targets_morph_index');
      $table->timestamps();

      $table->unique(
        ['internal_notification_id', 'notifiable_type', 'notifiable_id'],
        'internal_notification_targets_unique'
      );
    });

    Schema::create('internal_notification_reads', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('internal_notification_id')
        ->constrained('internal_notifications')
        ->cascadeOnDelete();
      $table->morphs('reader', 'notif_read_morph_index');
      $table->timestamp('read_at')->nullable();
      $table->timestamps();

      $table->unique(
        ['internal_notification_id', 'reader_type', 'reader_id'],
        'internal_notification_reads_unique'
      );
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('internal_notification_reads');
    Schema::dropIfExists('internal_notification_targets');
    Schema::dropIfExists('internal_notifications');
  }
};
