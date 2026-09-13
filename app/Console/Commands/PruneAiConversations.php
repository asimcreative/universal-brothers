<?php

namespace App\Console\Commands;

use App\Models\AiConversation;
use Illuminate\Console\Command;

/**
 * Retention for stored chats.
 *
 * Visitors type real personal details into chat boxes — names, phone numbers,
 * sometimes medical or family circumstances — so conversations are not kept
 * indefinitely. A conversation that produced an enquiry is kept, because that
 * enquiry is a business record the team is actively working.
 */
class PruneAiConversations extends Command
{
    protected $signature = 'ai:prune {--days= : Override the configured retention window}';

    protected $description = 'Delete AI conversations older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('ai.history.retention_days'));

        if ($days <= 0) {
            $this->info('Retention is disabled (retention_days is 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $deleted = AiConversation::where('lead_captured', false)
            ->where(function ($query) use ($cutoff) {
                $query->where('last_activity_at', '<', $cutoff)
                    ->orWhereNull('last_activity_at');
            })
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} conversations older than {$days} days.");

        return self::SUCCESS;
    }
}
