<?php

namespace App\Console\Commands;

use App\Models\ActivityEvent;
use App\Models\UiEvent;
use App\Models\UserSession;
use Illuminate\Console\Command;

class PruneActivityData extends Command
{
    protected $signature = 'activity:prune';

    protected $description = 'Elimina telemetría vencida y reporta auditoría lista para archivo';

    public function handle(): int
    {
        $ui = UiEvent::query()
            ->where('occurred_at', '<', now()->subDays(config('activity.ui_retention_days', 90)))
            ->delete();
        $sessions = UserSession::query()
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->subDays(config('activity.session_retention_days', 365)))
            ->delete();
        $archiveEligible = ActivityEvent::query()
            ->where('created_at', '<', now()->subDays(config('activity.audit_retention_days', 730)))
            ->count();

        $this->info("Eventos UI eliminados: {$ui}; sesiones eliminadas: {$sessions}.");
        $this->line("Eventos canónicos listos para archivo externo (no se borran por inmutabilidad): {$archiveEligible}");

        return self::SUCCESS;
    }
}
