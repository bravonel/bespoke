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

        @if ($notifications->isNotEmpty())
            <section class="panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-pink-50 text-[#e91e8c]">
                            <x-heroicon-o-sparkles class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-950">Novedades</h2>
                            <p class="text-xs text-slate-500">Asignaciones, comentarios y cambios que requieren tu atención.</p>
                        </div>
                    </div>

                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('task-notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button class="button-secondary py-2 text-xs">Marcar todas como leídas</button>
                        </form>
                    @endif
                </div>

                <div class="divide-y divide-stone-100">
                    @foreach ($notifications as $notification)
                        @php
                            $kind = data_get($notification->data, 'kind');
                            $icon = match ($kind) {
                                'task.commented' => 'chat-bubble-left-right',
                                'task.status_changed' => 'arrow-path',
                                'coverage.assigned' => 'user-plus',
                                'coverage.revoked' => 'user-minus',
                                default => 'clipboard-document-check',
                            };
                        @endphp
                        <form method="POST" action="{{ route('task-notifications.open', $notification) }}">
                            @csrf
                            <button class="group flex w-full items-start gap-3 px-5 py-4 text-left transition hover:bg-stone-50 sm:px-6 {{ $notification->read_at ? 'opacity-65' : 'bg-amber-50/35' }}">
                                <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-stone-200 bg-white text-slate-500 transition group-hover:text-slate-800">
                                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-4 w-4" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <strong class="truncate text-sm text-slate-900">{{ data_get($notification->data, 'title', 'Novedad en una tarea') }}</strong>
                                        @unless ($notification->read_at)
                                            <span class="h-2 w-2 shrink-0 rounded-full bg-[#e91e8c]" aria-label="Sin leer"></span>
                                        @endunless
                                    </span>
                                    <span class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ data_get($notification->data, 'message') }}</span>
                                </span>
                                <span class="shrink-0 text-[10px] font-medium text-slate-400">{{ $notification->created_at->diffForHumans(short: true) }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tasks->isEmpty())
            <div class="panel p-8 text-center text-slate-500">
                No tienes tareas asignadas. Cuando el equipo te asigne trabajo, aparecerá aquí.
            </div>
        @else

            @php
                $sectionMeta = [
                    'today'       => ['label' => 'Hoy', 'description' => \App\Models\Task::formatEstimatedMinutes($todayEstimatedMinutes).' planeadas'],
                    'upcoming'    => ['label' => 'Próximas', 'description' => 'Planeadas después de hoy'],
                    'unscheduled' => ['label' => 'Sin fecha de trabajo', 'description' => 'Tienen participantes pero no día asignado'],
                    'done'        => ['label' => 'Entregadas y finalizadas', 'description' => 'Fuera de la carga activa'],
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
                                    $isOverdue = ! in_array($task->status, \App\Models\Task::inactiveStatuses(), true) && $task->due_at?->isPast();
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
                                                @if ($task->coverage_name)
                                                    <span class="rounded-full bg-pink-50 px-2 py-1 normal-case tracking-normal text-[#c21875]">Cubriendo a {{ $task->coverage_name }}</span>
                                                @endif
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

                                                @if ($task->personal_priority)
                                                    <span class="rounded-full bg-slate-950 px-2.5 py-1 text-xs font-bold text-white">Orden #{{ $task->personal_priority }}</span>
                                                @endif

                                                @if ($task->status === 'todo' && $task->blocked_reason)
                                                    <span class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs normal-case tracking-normal text-rose-800">{{ $task->blocked_reason }}</span>
                                                @endif

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

                                    @if (! in_array($task->status, \App\Models\Task::inactiveStatuses(), true))
                                        <form method="POST" action="{{ route('tasks.update-schedule', $task) }}" class="mt-4" onclick="event.stopPropagation()">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="planned_for" value="{{ today()->addDay()->format('Y-m-d') }}">
                                            @if ($task->assignments->first())
                                                <input type="hidden" name="user_id" value="{{ $task->assignments->first()->user_id }}">
                                            @endif
                                            <x-icon-button type="submit" label="Pasar tarea a mañana" icon="calendar-days" size="sm" />
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
