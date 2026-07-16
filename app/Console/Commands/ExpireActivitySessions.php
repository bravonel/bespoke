<?php

namespace App\Console\Commands;

use App\Services\Activity\UserSessionService;
use Illuminate\Console\Command;

class ExpireActivitySessions extends Command
{
    protected $signature = 'activity:expire-sessions';

    protected $description = 'Cierra sesiones de actividad que superaron el tiempo de inactividad';

    public function handle(UserSessionService $sessions): int
    {
        $this->info("Sesiones cerradas: {$sessions->expireStale()}");

        return self::SUCCESS;
    }
}
