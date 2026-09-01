<?php

namespace App\Console\Commands;

use App\Services\Activity\ActivityAnalyticsAggregator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class AggregateActivityAnalytics extends Command
{
    protected $signature = 'activity:aggregate-analytics
        {--from= : Primera fecha a agregar (Y-m-d); por defecto ayer}
        {--to= : Última fecha a agregar (Y-m-d); por defecto igual que --from}';

    protected $description = 'Genera métricas diarias permanentes a partir de telemetría sin eliminar datos crudos';

    public function handle(ActivityAnalyticsAggregator $aggregator): int
    {
        try {
            $from = filled($this->option('from'))
                ? Carbon::createFromFormat('Y-m-d', (string) $this->option('from'))->startOfDay()
                : now()->subDay()->startOfDay();
            $to = filled($this->option('to'))
                ? Carbon::createFromFormat('Y-m-d', (string) $this->option('to'))->startOfDay()
                : $from->copy();
        } catch (Throwable) {
            $this->error('Las fechas deben usar el formato Y-m-d.');

            return self::FAILURE;
        }

        if ($from->gt($to)) {
            $this->error('--from no puede ser posterior a --to.');

            return self::FAILURE;
        }

        if ($from->diffInDays($to) > 3660) {
            $this->error('El rango máximo permitido es de 10 años.');

            return self::FAILURE;
        }

        $totals = ['ui_metrics' => 0, 'session_metrics' => 0];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $result = $aggregator->aggregateDate($date);
            $totals['ui_metrics'] += $result['ui_metrics'];
            $totals['session_metrics'] += $result['session_metrics'];
        }

        $this->info(sprintf(
            'Analítica agregada de %s a %s: %d métricas UI y %d métricas de sesión.',
            $from->toDateString(),
            $to->toDateString(),
            $totals['ui_metrics'],
            $totals['session_metrics'],
        ));

        return self::SUCCESS;
    }
}
