<?php

namespace App\Console\Commands;

use App\Models\AiConversation;
use App\Models\AssistantRunMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneAssistantData extends Command
{
    protected $signature = 'ai:prune-data
    {--dry-run : Report rows without deleting them}
    {--conversation-days= : Override the configured conversation retention window}
    {--telemetry-days= : Override the configured telemetry retention window}';

    protected $description = 'Prune expired EduManager assistant conversations and telemetry';

    public function handle(): int
    {
        $conversationDays = $this->days(
            $this->option('conversation-days'),
            (int) config('ai.assistant.conversation_retention_days', 365)
        );
        $telemetryDays = $this->days(
            $this->option('telemetry-days'),
            (int) config('ai.telemetry.retention_days', 180)
        );
        $conversationCount = $this->pruneConversations($conversationDays);
        $telemetryCount = $this->pruneTelemetry($telemetryDays);

        $this->line("Conversations: {$conversationCount}");
        $this->line("Telemetry runs: {$telemetryCount}");

        return self::SUCCESS;
    }

    private function pruneConversations(int $days): int
    {
        if ($days === 0) {
            return 0;
        }

        $query = AiConversation::query()->where(
            'updated_at',
            '<',
            Carbon::now()->subDays($days)
        );
        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            return $count;
        }

        $query->select('id')->chunkById(250, function ($conversations): void {
            $ids = $conversations->pluck('id')->all();

            AiConversation::query()
                ->whereIn('id', $ids)
                ->each(function (AiConversation $conversation): void {
                    $conversation->messages()->delete();
                    $conversation->delete();
                });
        }, 'id');

        return $count;
    }

    private function pruneTelemetry(int $days): int
    {
        if ($days === 0) {
            return 0;
        }

        $query = AssistantRunMetric::query()->where(
            'created_at',
            '<',
            Carbon::now()->subDays($days)
        );
        $count = (clone $query)->count();

        if (! $this->option('dry-run')) {
            $query->delete();
        }

        return $count;
    }

    private function days(mixed $override, int $configured): int
    {
        $days = $override === null ? $configured : (int) $override;

        return max(0, $days);
    }
}
