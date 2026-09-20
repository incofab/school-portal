<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    $conversationsTable = config(
      'ai.conversations.tables.conversations',
      'agent_conversations'
    );
    $messagesTable = config(
      'ai.conversations.tables.messages',
      'agent_conversation_messages'
    );

    Schema::create($conversationsTable, function (Blueprint $table) {
      $table->string('id', 36)->primary();
      $table->string('participant_type')->nullable();
      $table->unsignedBigInteger('participant_id')->nullable();
      $table->unsignedBigInteger('institution_id')->nullable();
      $table->string('guest_token', 64)->nullable();
      $table->string('title');
      $table->text('summary')->nullable();
      $table->timestamp('summary_updated_at')->nullable();
      $table->string('topic', 50)->nullable();
      $table->json('entities')->nullable();
      $table->timestamps();
      $table->timestamp('archived_at')->nullable();
      $table->json('meta')->nullable();

      $table->index(
        ['participant_type', 'participant_id', 'updated_at'],
        'participant_updated_at_index'
      );
      $table->index(
        'institution_id',
        'assistant_conversations_institution_index'
      );
      $table->index('guest_token', 'assistant_conversations_guest_token_index');
      $table->index('archived_at', 'assistant_conversations_archived_at_index');
    });

    Schema::create($messagesTable, function (Blueprint $table) {
      $table->string('id', 36)->primary();
      $table->string('conversation_id', 36)->index();
      $table->string('participant_type')->nullable();
      $table->unsignedBigInteger('participant_id')->nullable();
      $table->string('agent');
      $table->string('role', 25);
      $table->text('content');
      $table->text('attachments');
      $table->text('tool_calls');
      $table->text('tool_results');
      $table->text('usage');
      $table->text('meta');
      $table->text('approval_state')->nullable();
      $table->timestamps();

      $table->index(
        ['conversation_id', 'participant_type', 'participant_id', 'updated_at'],
        'conversation_index'
      );
      $table->index(
        ['participant_type', 'participant_id'],
        'participant_index'
      );
    });

    Schema::create('assistant_action_executions', function (
      Blueprint $table
    ) use ($conversationsTable) {
      $table->uuid('id')->primary();
      $table->string('conversation_id', 36);
      $table->unsignedBigInteger('institution_id')->nullable();
      $table->string('tool_name');
      $table->string('idempotency_key')->unique();
      $table->string('confirmation_token_hash', 64);
      $table->json('arguments');
      $table->json('preview');
      $table->string('status', 25)->default('pending');
      $table->json('result')->nullable();
      $table->timestamp('confirmed_at')->nullable();
      $table->timestamp('executed_at')->nullable();
      $table->timestamps();

      $table->index(['conversation_id', 'status']);
      $table->index(['institution_id', 'created_at']);
      $table
        ->foreign('conversation_id')
        ->references('id')
        ->on($conversationsTable)
        ->cascadeOnDelete();
      $table
        ->foreign('institution_id')
        ->references('id')
        ->on('institutions')
        ->nullOnDelete();
    });

    Schema::create('assistant_run_metrics', function (Blueprint $table) {
      $table->id();
      $table->string('conversation_id', 36);
      $table->string('message_id', 36)->nullable();
      $table->unsignedBigInteger('institution_id')->nullable();
      $table->unsignedBigInteger('user_id')->nullable();
      $table->string('actor_role', 50)->nullable();
      $table->boolean('is_guest')->default(false);
      $table->string('outcome', 40);
      $table->string('failure_reason', 60)->nullable();
      $table->unsignedInteger('duration_ms')->default(0);
      $table->unsignedSmallInteger('model_calls')->default(0);
      $table->unsignedSmallInteger('tool_calls')->default(0);
      $table->unsignedSmallInteger('retrieval_calls')->default(0);
      $table->unsignedInteger('input_tokens')->nullable();
      $table->unsignedInteger('output_tokens')->nullable();
      $table->boolean('grounded')->default(false);
      $table->unsignedSmallInteger('source_count')->default(0);
      $table->json('tools_used')->nullable();
      $table->string('feedback_rating', 20)->nullable();
      $table->timestamps();

      $table->index(['institution_id', 'created_at']);
      $table->index(['outcome', 'created_at']);
      $table->index('message_id');
      $table->index('conversation_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('assistant_run_metrics');
    Schema::dropIfExists('assistant_action_executions');
    Schema::dropIfExists(
      config('ai.conversations.tables.messages', 'agent_conversation_messages')
    );
    Schema::dropIfExists(
      config('ai.conversations.tables.conversations', 'agent_conversations')
    );
  }
};
