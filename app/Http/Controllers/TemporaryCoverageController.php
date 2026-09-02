<?php

namespace App\Http\Controllers;

use App\Models\TemporaryCoverage;
use App\Models\User;
use App\Notifications\PersonalAttentionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TemporaryCoverageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
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
        ]);

        $delegate = User::query()->findOrFail($validated['delegate_user_id']);
        if ($delegate->isAdmin() || $delegate->hasRole(User::ROLE_ADMIN)) {
            throw ValidationException::withMessages([
                'delegate_user_id' => 'Selecciona a otro colaborador operativo.',
            ]);
        }

        $overlaps = $request->user()->coveragesCreated()
            ->whereNull('revoked_at')
            ->whereDate('starts_on', '<=', $validated['ends_on'])
            ->whereDate('ends_on', '>=', $validated['starts_on'])
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'starts_on' => 'Ya tienes una cobertura activa o programada dentro de esas fechas.',
            ]);
        }

        $coverage = $request->user()->coveragesCreated()->create($validated);
        $this->notifyDelegate(
            $coverage,
            'coverage.assigned',
            'Nueva cobertura temporal',
            "Cubrirás el trabajo de {$request->user()->name} del {$coverage->starts_on->format('d/m/Y')} al {$coverage->ends_on->format('d/m/Y')}.",
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
