<?php

namespace App\Http\Controllers;

use App\Models\TemporaryCoverage;
use App\Models\User;
use App\Notifications\PersonalAttentionNotification;
use App\Services\Access\OperationalAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TemporaryCoverageController extends Controller
{
    public function store(Request $request, OperationalAccess $access): RedirectResponse
    {
        abort_unless(Schema::hasTable('temporary_coverage_scopes'), 503);

        $validated = $request->validate([
            'delegate_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
                Rule::notIn([$request->user()->id]),
            ],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'note' => ['nullable', 'string', 'max:500'],
            'scope_mode' => ['required', Rule::in(['all', 'selected'])],
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'distinct', 'exists:clients,id'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'distinct', 'exists:projects,id'],
        ]);

        $delegate = User::query()->findOrFail($validated['delegate_user_id']);
        if ($delegate->isAdmin() || $delegate->hasRole(User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'delegate_user_id' => 'Selecciona a otro colaborador operativo.',
            ]);
        }

        $delegableProjects = $access->delegableProjects($request->user())->get(['id', 'client_id']);
        $delegableProjectIds = $delegableProjects->pluck('id')->map(fn ($id) => (int) $id);
        $delegableClientIds = $delegableProjects->pluck('client_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $clientIds = collect($validated['client_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $projectIds = collect($validated['project_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();

        if ($clientIds->diff($delegableClientIds)->isNotEmpty() || $projectIds->diff($delegableProjectIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scope_mode' => 'Solo puedes delegar cuentas y proyectos que forman parte de tu trabajo.',
            ]);
        }

        if ($validated['scope_mode'] === 'selected' && $clientIds->isEmpty() && $projectIds->isEmpty()) {
            throw ValidationException::withMessages([
                'scope_mode' => 'Selecciona al menos una cuenta o un proyecto.',
            ]);
        }

        $newProjectIds = $validated['scope_mode'] === 'all'
            ? $delegableProjectIds
            : $delegableProjects
                ->filter(fn ($project) => $clientIds->contains((int) $project->client_id) || $projectIds->contains((int) $project->id))
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        $overlappingCoverages = $request->user()->coveragesCreated()
            ->whereNull('revoked_at')
            ->whereDate('starts_on', '<=', $validated['ends_on'])
            ->whereDate('ends_on', '>=', $validated['starts_on'])
            ->with('scopes')
            ->get();

        $overlaps = $overlappingCoverages->contains(function (TemporaryCoverage $coverage) use ($delegableProjects, $newProjectIds, $validated): bool {
            if ($validated['scope_mode'] === 'all' || $coverage->scopes->isEmpty()) {
                return true;
            }

            $coveredClientIds = $coverage->scopes->pluck('client_id')->filter()->map(fn ($id) => (int) $id);
            $coveredProjectIds = $coverage->scopes->pluck('project_id')->filter()->map(fn ($id) => (int) $id);
            $existingProjectIds = $delegableProjects
                ->filter(fn ($project) => $coveredClientIds->contains((int) $project->client_id) || $coveredProjectIds->contains((int) $project->id))
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            return $newProjectIds->intersect($existingProjectIds)->isNotEmpty();
        });

        if ($overlaps) {
            throw ValidationException::withMessages([
                'scope_mode' => 'Esa cuenta o proyecto ya tiene cobertura durante alguna de las fechas seleccionadas.',
            ]);
        }

        $coverage = DB::transaction(function () use ($request, $validated, $clientIds, $projectIds): TemporaryCoverage {
            $coverage = $request->user()->coveragesCreated()->create([
                'delegate_user_id' => $validated['delegate_user_id'],
                'starts_on' => $validated['starts_on'],
                'ends_on' => $validated['ends_on'],
                'note' => $validated['note'] ?? null,
            ]);

            if ($validated['scope_mode'] === 'selected') {
                $coverage->scopes()->createMany([
                    ...$clientIds->map(fn (int $id) => ['client_id' => $id])->all(),
                    ...$projectIds->map(fn (int $id) => ['project_id' => $id])->all(),
                ]);
            }

            return $coverage->load(['scopes.client', 'scopes.project']);
        });
        $this->notifyDelegate(
            $coverage,
            'coverage.assigned',
            'Nueva cobertura temporal',
            "Cubrirás {$coverage->scopeSummary()} de {$request->user()->name}, del {$coverage->starts_on->format('d/m/Y')} al {$coverage->ends_on->format('d/m/Y')}.",
        );

        return back()->with('status', 'Cobertura temporal programada.');
    }

    public function destroy(Request $request, TemporaryCoverage $coverage): RedirectResponse
    {
        abort_unless((int) $coverage->owner_user_id === (int) $request->user()->id, 403);

        if ($coverage->revoked_at === null && ! $coverage->ends_on->isBefore(today())) {
            $coverage->update(['revoked_at' => now()]);
            $this->notifyDelegate(
                $coverage,
                'coverage.revoked',
                'Cobertura cancelada',
                "{$request->user()->name} canceló la cobertura temporal.",
            );
        }

        return back()->with('status', 'Cobertura temporal cancelada.');
    }

    private function notifyDelegate(TemporaryCoverage $coverage, string $kind, string $title, string $message): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $coverage->delegate->notify(new PersonalAttentionNotification([
            'kind' => $kind,
            'title' => $title,
            'message' => $message,
            'url' => route('profile', absolute: false),
            'coverage_id' => $coverage->id,
            'actor_id' => $coverage->owner_user_id,
            'actor_name' => $coverage->owner->name,
        ]));
    }
}
