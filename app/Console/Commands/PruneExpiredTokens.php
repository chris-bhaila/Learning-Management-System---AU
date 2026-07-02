<?php

namespace App\Console\Commands;

use App\Models\Token;
use Illuminate\Console\Command;

class PruneExpiredTokens extends Command
{
    protected $signature = 'tokens:prune';
    protected $description = 'Hard-delete tokens that expired more than 7 days ago';

    public function handle(): int
    {
        $deleted = Token::where('expires_at', '<', now()->subDays(7))->delete();

        $this->info("Pruned {$deleted} expired token(s).");

        return Command::SUCCESS;
    }
}
