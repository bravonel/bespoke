<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Mi espacio</p>
                <h1 class="page-title mt-2">Mis tareas</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Tu carga de hoy, próximos pendientes y tareas sin fecha de trabajo.</p>
            </div>

            @if ($overdue > 0)
                <div class="flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    <span>{{ $overdue }} vencida{{ $overdue > 1 ? 's' : '' }}</span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="shell space-y-8">

        @if ($tasks->isEmpty())
            <div class="panel p-8 text-center text-slate-500">
                No tienes tareas asignadas. Cuando el equipo te asigne trabajo, aparecerá aquí.
            </div>
        @else

            @php
                $sectionMeta = [
                    'today'       => ['label' => 'Hoy', 'description' => \App\Models\Task::formatEstimatedMinutes($todayEstimatedMinutes).' planeadas'],
                    'upcoming'    => ['label' => 'Próximas', 'description' => 'Planeadas después de hoy'],
                    'unscheduled' => ['label' => 'Sin fecha de trabajo', 'description' => 'Tienen responsable pero no día asignado'],
                    'done'        => ['label' => 'Listas', 'description' => 'Tareas ya cerradas'],
                ];
            @endphp

            @foreach ($sectionMeta as $sectionKey => $section)
                @if ($sections[$sectionKey]->isNotEmpty())
                    <section>
                        <div class="mb-4 flex flex-wrap items-center gap-3">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $section['label'] }}</h2>
                            <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $sections[$sectionKey]->count() }}</span>
                            <span class="text-sm text-slate-500">{{ $section['description'] }}</span>
                        </div>

                        <div class="space-y-3">
                            @foreach ($sections[$sectionKey] as $task)
                                @php
                                    $isOverdue = $task->status !== 'done' && $task->due_at?->isPast();
                                    $subtaskProgress = $task->subtasks_count > 0
                                        ? (int) round(($task->completed_subtasks_count / $task->subtasks_count) * 100)
                                        : 0;
                                @endphp

                                <div
                                    class="panel p-5 cursor-pointer transition hover:shadow-md"
                                    onclick="window.dispatchEvent(new CustomEvent('open-task-drawer', { detail: { url: '{{ route('tasks.show', $task) }}' } }))"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                <span>{{ $task->project->name }}</span>
                                                <span>·</span>
                                                <span>{{ $task->project->client->name }}</span>
                                                @if ($task->project->brand)
                                                    <span>·</span>
                                                    <span>{{ $task->project->brand->name }}</span>
                                                @endif
                                            </div>

                                            <h3 class="mt-2 text-base font-semibold text-slate-950">{{ $task->title }}</h3>

                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    {{ $taskPriorityMeta[$task->priority]['label'] }}
                                                </span>

                                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold
                                                    {{ $isOverdue
                                                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                                                        : 'border-stone-200 bg-stone-50 text-slate-500' }}">
                                                    Entrega {{ $task->due_at?->translatedFormat('d M Y') ?: 'sin fecha' }}
                                                </span>

                                                <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                    Carga {{ $task->planned_for?->translatedFormat('d M Y') ?: 'sin fecha' }}
                                                </span>

                                                <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                    {{ \App\Models\Task::formatEstimatedMinutes($task->estimated_minutes) }}
                                                </span>

                                                @if ($task->subtasks_count > 0)
                                                    <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                        {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }} subtareas
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <a
                                            href="{{ route('projects.show', $task->project) }}"
                                            class="shrink-0 text-xs font-medium hover:underline"
                                            style="color:var(--brand-amber)"
                                            onclick="event.stopPropagation()"
                                        >Ver tablero →</a>
                                    </div>

                                    @if ($task->status !== 'done')
                                        <form method="POST" action="{{ route('tasks.update-schedule', $task) }}" class="mt-4" onclick="event.stopPropagation()">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="planned_for" value="{{ today()->addDay()->format('Y-m-d') }}">
                                            <button class="button-secondary px-3 py-1.5 text-xs">Pasar a mañana</button>
                                        </form>
                                    @endif

                                    @if ($task->subtasks_count > 0)
                                        <div class="mt-4">
                                            <div class="progress-track mt-0">
                                                <span class="progress-fill" style="width: {{ $subtaskProgress }}%"></span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

        @endif
    </div>
</x-app-layout>
