<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Console\Command;

class NotifyExpiredTokens extends Command
{
    protected $signature = 'tokens:notify-expired';
    protected $description = 'Log a permanent expiry notification for tokens whose time limit just passed';

    public function handle(TokenRepositoryInterface $tokens): int
    {
        $expired = $tokens->getExpiredUnnotified();

        foreach ($expired as $token) {
            $tokens->logExpiry($token, 'time_limit');
        }

        $this->info("Notified {$expired->count()} newly time-expired token(s).");

        return Command::SUCCESS;
    }
}
