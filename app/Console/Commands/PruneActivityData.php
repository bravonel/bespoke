<?php

namespace App\Console\Commands;

use App\Models\ActivityDailyMetric;
use App\Models\ActivityEvent;
use App\Models\UiEvent;
use App\Models\UserSession;
use Illuminate\Console\Command;

class PruneActivityData extends Command
{
    protected $signature = 'activity:prune';

    protected $description = 'Reporta telemetría lista para archivo sin eliminar datos crudos';

    public function handle(): int
    {
        $ui = UiEvent::query()
            ->where('occurred_at', '<', now()->subDays(config('activity.ui_retention_days', 730)))
            ->count();
        $sessions = UserSession::query()
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->subDays(config('activity.session_retention_days', 730)))
            ->count();
        $archiveEligible = ActivityEvent::query()
            ->where('created_at', '<', now()->subDays(config('activity.audit_retention_days', 730)))
            ->count();

        $aggregatedThrough = ActivityDailyMetric::query()->max('metric_date');

        $this->info('Modo seguro: no se eliminó ningún registro.');
        $this->line("Candidatos para archivo — eventos UI: {$ui}; sesiones: {$sessions}.");
        $this->line('Analítica agregada hasta: '.($aggregatedThrough ?: 'sin métricas todavía').'.');
        $this->line("Eventos canónicos listos para archivo externo (no se borran por inmutabilidad): {$archiveEligible}");

        return self::SUCCESS;
    }
}
