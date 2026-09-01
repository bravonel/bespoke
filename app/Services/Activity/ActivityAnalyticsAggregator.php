<?php

namespace App\Services\Activity;

use App\Models\ActivityDailyMetric;
use App\Models\UiEvent;
use App\Models\UserSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityAnalyticsAggregator
{
    /** @return array{ui_metrics: int, session_metrics: int} */
    public function aggregateDate(Carbon $date): array
    {
        $day = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $uiRows = $this->uiRows($day, $end);
        $sessionRows = $this->sessionRows($day, $end);

        DB::transaction(function () use ($day, $uiRows, $sessionRows): void {
            ActivityDailyMetric::query()
                ->whereDate('metric_date', $day->toDateString())
                ->whereIn('metric_type', ['ui_event', 'session'])
                ->delete();

            foreach ([...$uiRows, ...$sessionRows] as $row) {
                ActivityDailyMetric::query()->create($row);
            }
        });

        return [
            'ui_metrics' => count($uiRows),
            'session_metrics' => count($sessionRows),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function uiRows(Carbon $start, Carbon $end): array
    {
        return UiEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->get()
            ->groupBy(fn (UiEvent $event) => $this->fingerprint([
                'date' => $start->toDateString(),
                'type' => 'ui_event',
                'user_id' => $event->user_id,
                'project_id' => $event->project_id,
                'event_name' => $event->event_name,
                'page' => $event->page,
                'target' => $event->target,
            ]))
            ->map(function ($events, string $fingerprint) use ($start): array {
                /** @var UiEvent $event */
                $event = $events->first();

                return [
                    'metric_date' => $start->toDateString(),
                    'metric_type' => 'ui_event',
                    'user_id' => $event->user_id,
                    'project_id' => $event->project_id,
                    'dimension_key' => $event->event_name,
                    'page' => $event->page,
                    'dimensions' => array_filter(['target' => $event->target]),
                    'event_count' => $events->count(),
                    'session_count' => $events->pluck('user_session_id')->filter()->unique()->count(),
                    'active_seconds' => 0,
                    'idle_seconds' => 0,
                    'fingerprint' => $fingerprint,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function sessionRows(Carbon $start, Carbon $end): array
    {
        return UserSession::query()
            ->whereBetween('started_at', [$start, $end])
            ->get()
            ->groupBy(fn (UserSession $session) => $this->fingerprint([
                'date' => $start->toDateString(),
                'type' => 'session',
                'user_id' => $session->user_id,
                'channel' => $session->channel,
                'device_type' => $session->device_type,
                'browser' => $session->browser,
                'platform' => $session->platform,
            ]))
            ->map(function ($sessions, string $fingerprint) use ($start): array {
                /** @var UserSession $session */
                $session = $sessions->first();

                return [
                    'metric_date' => $start->toDateString(),
                    'metric_type' => 'session',
                    'user_id' => $session->user_id,
                    'project_id' => null,
                    'dimension_key' => $session->channel,
                    'page' => null,
                    'dimensions' => array_filter([
                        'device_type' => $session->device_type,
                        'browser' => $session->browser,
                        'platform' => $session->platform,
                    ]),
                    'event_count' => 0,
                    'session_count' => $sessions->count(),
                    'active_seconds' => $sessions->sum('active_seconds'),
                    'idle_seconds' => $sessions->sum('idle_seconds'),
                    'fingerprint' => $fingerprint,
                ];
            })
            ->values()
            ->all();
    }

    private function fingerprint(array $dimensions): string
    {
        return hash('sha256', json_encode($dimensions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
