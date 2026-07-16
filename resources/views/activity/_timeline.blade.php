<section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Trazabilidad</div>
            <h2 class="mt-1 text-lg font-semibold text-slate-950">Actividad reciente</h2>
        </div>
        <a href="{{ route('activity.index', isset($project) ? ['project_id' => $project->id] : []) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Ver todo</a>
    </div>

    <div class="mt-5 space-y-4">
        @forelse ($recentActivity as $event)
            <div class="flex gap-3">
                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-indigo-500"></span>
                <div class="min-w-0">
                    <p class="text-sm text-slate-700">
                        <span class="font-semibold text-slate-950">{{ $event->actor?->name ?? 'Sistema' }}</span>
                        · {{ \App\Services\Activity\ActivityLabels::get($event->event_type) }}
                    </p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ $event->created_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">La actividad aparecerá aquí conforme el equipo trabaje.</p>
        @endforelse
    </div>
</section>
