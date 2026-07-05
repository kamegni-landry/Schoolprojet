<?php

namespace App\Console\Commands;

use App\Models\USSDSession;
use Illuminate\Console\Command;

class CleanExpiredUssdSessions extends Command
{
    protected $signature   = 'ussd:clean-sessions';
    protected $description = 'Supprime les sessions USSD expirées';

    public function handle(): int
    {
        $deleted = USSDSession::where('expires_at', '<', now())
            ->orWhere('status', 'ended')
            ->delete();

        $this->info("✅ {$deleted} session(s) USSD supprimée(s).");

        return self::SUCCESS;
    }
}
