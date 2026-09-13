<?php

namespace App\Console\Commands;

use App\Support\Ai\KnowledgeIndexer;
use Illuminate\Console\Command;

class RebuildAiKnowledgeIndex extends Command
{
    protected $signature = 'ai:index';

    protected $description = 'Rebuild the AI assistant knowledge index from the current database content';

    public function handle(KnowledgeIndexer $indexer): int
    {
        $this->info('Rebuilding AI knowledge index...');

        $count = $indexer->rebuild();

        $this->info("Indexed {$count} records.");

        return self::SUCCESS;
    }
}
