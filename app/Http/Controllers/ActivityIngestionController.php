<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\UiEvent;
use App\Models\User;
use App\Services\Access\OperationalAccess;
use App\Services\Activity\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivityIngestionController extends Controller
{
    public function heartbeat(Request $request, UserSessionService $sessions): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
            'page' => ['nullable', 'string', 'max:255'],
        ]);

        $session = $sessions->heartbeat(
            $request,
            (bool) $validated['active'],
            $validated['page'] ?? null,
        );

        return response()->json([
            'recorded' => $session !== null,
            'active_seconds' => $session?->active_seconds,
            'idle_seconds' => $session?->idle_seconds,
        ]);
    }

    public function uiEvents(
        Request $request,
        UserSessionService $sessions,
        OperationalAccess $access,
    ): JsonResponse {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.event_name' => ['required', 'string', Rule::in(config('activity.ui_events'))],
            'events.*.page' => ['nullable', 'string', 'max:255'],
            'events.*.target' => ['nullable', 'string', 'max:120'],
            'events.*.project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'events.*.entity_type' => ['nullable', 'string', Rule::in(array_keys($this->entityTypes()))],
            'events.*.entity_id' => ['nullable', 'integer', 'min:1'],
            'events.*.metadata' => ['nullable', 'array'],
            'events.*.occurred_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $session = $sessions->current($request, $user);
        $allowedProjectIds = collect($access->projects($user)->pluck('id'))->all();

        DB::transaction(function () use ($validated, $user, $session, $allowedProjectIds): void {
            foreach ($validated['events'] as $event) {
                $projectId = $event['project_id'] ?? null;

                abort_if($projectId && ! in_array($projectId, $allowedProjectIds, true), 403);

                $occurredAt = isset($event['occurred_at'])
                    ? Carbon::parse($event['occurred_at'])
                    : now();

                if ($occurredAt->lt(now()->subMinutes(10)) || $occurredAt->gt(now()->addMinute())) {
                    $occurredAt = now();
                }

                UiEvent::query()->create([
                    'user_id' => $user->id,
                    'user_session_id' => $session?->id,
                    'event_name' => $event['event_name'],
                    'page' => $event['page'] ?? null,
                    'target' => $event['target'] ?? null,
                    'project_id' => $projectId,
                    'entity_type' => isset($event['entity_type'])
                        ? $this->entityTypes()[$event['entity_type']]
                        : null,
                    'entity_id' => $event['entity_id'] ?? null,
                    'metadata' => Arr::only($event['metadata'] ?? [], [
                        'filter_names',
                        'result_count',
                        'source',
                    ]),
                    'occurred_at' => $occurredAt,
                ]);
            }
        });

        return response()->json(['recorded' => count($validated['events'])], 202);
    }

    private function entityTypes(): array
    {
        return [
            'client' => Client::class,
            'brand' => Brand::class,
            'project' => Project::class,
            'task' => Task::class,
            'user' => User::class,
        ];
    }
}
