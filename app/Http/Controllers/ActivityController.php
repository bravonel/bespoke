<?php

namespace App\Http\Controllers;

use App\Models\ActivityAlert;
use App\Models\ActivityEvent;
use App\Models\UiEvent;
use App\Models\User;
use App\Models\UserSession;
use App\Services\Access\OperationalAccess;
use App\Services\Activity\ActivityLabels;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request, OperationalAccess $access, AuditLogger $audit)
    {
        $user = $request->user();
        $filters = $this->filters($request, $user);
        $query = $this->eventQuery($user, $filters);

        $audit->recordSystem('activity.center_viewed', $user, [
            'filters' => array_keys(array_filter($filters)),
        ]);

        $sessionQuery = UserSession::query()->with('user')->latest('started_at');
        $uiQuery = UiEvent::query()->with(['user', 'project'])->latest('occurred_at');

        if (! $user->canViewTeamActivity()) {
            $sessionQuery->where('user_id', $user->id);
            $uiQuery->where('user_id', $user->id);
        } elseif ($filters['actor_id']) {
            $sessionQuery->where('user_id', $filters['actor_id']);
            $uiQuery->where('user_id', $filters['actor_id']);
        }

        if ($filters['project_id']) {
            $uiQuery->where('project_id', $filters['project_id']);
        }

        $summary = [
            'events' => (clone $query)->count(),
            'changes' => (clone $query)->whereNotNull('auditable_type')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'sessions' => (clone $sessionQuery)->count(),
        ];

        return view('activity.index', [
            'events' => $query->paginate(50)->withQueryString(),
            'sessions' => $sessionQuery->limit(20)->get(),
            'uiEvents' => $uiQuery->limit(30)->get(),
            'alerts' => $user->canViewTeamActivity()
                ? ActivityAlert::query()->with('user')->whereNull('resolved_at')->latest('detected_at')->limit(10)->get()
                : collect(),
            'filters' => $filters,
            'users' => $user->canViewTeamActivity()
                ? User::query()->orderBy('name')->get()
                : collect([$user]),
            'projects' => $access->projects($user)->orderBy('name')->get(),
            'eventTypes' => ActivityEvent::query()->distinct()->orderBy('event_type')->pluck('event_type'),
            'labels' => ActivityLabels::class,
            'canViewTeam' => $user->canViewTeamActivity(),
            'summary' => $summary,
        ]);
    }

    public function resolveAlert(Request $request, ActivityAlert $alert, AuditLogger $audit)
    {
        abort_unless($request->user()->canViewTeamActivity(), 403);

        $alert->update(['resolved_at' => now()]);
        $audit->record('activity.alert_resolved', $alert, $request->user(), [
            'alert_type' => $alert->alert_type,
        ]);

        return back()->with('status', 'Alerta resuelta.');
    }

    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $user = $request->user();
        $filters = $this->filters($request, $user);
        $audit->recordSystem('report.exported', $user, [
            'report' => 'activity',
            'format' => 'csv',
            'filters' => array_keys(array_filter($filters)),
        ]);
        $events = $this->eventQuery($user, $filters)->limit(10000)->get();

        return response()->streamDownload(function () use ($events): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Colaborador', 'Evento', 'Canal', 'Proyecto', 'Entidad', 'ID', 'Cambios']);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->created_at?->toIso8601String(),
                    $event->actor?->name ?: 'Sistema',
                    ActivityLabels::get($event->event_type),
                    $event->channel,
                    $event->project?->name,
                    $event->auditable_type ? class_basename($event->auditable_type) : null,
                    $event->auditable_id,
                    json_encode($event->metadata['changes'] ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($handle);
        }, 'actividad-bespoke-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request, AuditLogger $audit)
    {
        $user = $request->user();
        $filters = $this->filters($request, $user);
        $audit->recordSystem('report.exported', $user, [
            'report' => 'activity',
            'format' => 'pdf_print',
        ]);

        return view('activity.print', [
            'events' => $this->eventQuery($user, $filters)->limit(500)->get(),
            'labels' => ActivityLabels::class,
            'filters' => $filters,
        ]);
    }

    private function eventQuery(User $user, array $filters): Builder
    {
        return ActivityEvent::query()
            ->with(['actor', 'project', 'client', 'userSession'])
            ->when(! $user->canViewTeamActivity(), fn (Builder $query) => $query->where('actor_id', $user->id))
            ->when($user->canViewTeamActivity() && $filters['actor_id'], fn (Builder $query) => $query->where('actor_id', $filters['actor_id']))
            ->when($filters['project_id'], fn (Builder $query) => $query->where('project_id', $filters['project_id']))
            ->when($filters['event_type'], fn (Builder $query) => $query->where('event_type', $filters['event_type']))
            ->when($filters['channel'], fn (Builder $query) => $query->where('channel', $filters['channel']))
            ->whereBetween('created_at', [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->endOfDay(),
            ])
            ->latest('created_at');
    }

    private function filters(Request $request, User $user): array
    {
        $from = $request->date('from')?->toDateString() ?? today()->subDays(7)->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();

        return [
            'from' => $from,
            'to' => $to,
            'actor_id' => $user->canViewTeamActivity() ? ($request->integer('actor_id') ?: null) : $user->id,
            'project_id' => $request->integer('project_id') ?: null,
            'event_type' => $request->string('event_type')->toString() ?: null,
            'channel' => in_array($request->string('channel')->toString(), ['web', 'whatsapp', 'api', 'system'], true)
                ? $request->string('channel')->toString()
                : null,
        ];
    }
}
