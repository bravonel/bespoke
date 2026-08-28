<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">{{ $task->project->operationalCodeLabel() }} · Tarea</p>
                <h1 class="page-title">{{ $task->title }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ $task->project->name }}
                    · {{ $task->project->client->name }}
                    @if ($task->project->brand)
                        · {{ $task->project->brand->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-status-badge :value="$task->status" />
                <a href="{{ route('projects.show', $task->project) }}" class="button-secondary">Volver al tablero</a>
            </div>
        </div>
    </x-slot>

    <div class="shell space-y-7">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            <div class="metric-card">
                <div class="metric-label">Participantes</div>
                <div class="mt-3 space-y-1 text-sm font-semibold text-slate-950">
                    @forelse ($task->assignments as $assignment)
                        <div>{{ $assignment->user?->name ?: 'Sin participante' }} · {{ \App\Models\Task::formatEstimatedMinutes($assignment->estimated_minutes) }}</div>
                    @empty
                        <div>Sin participantes</div>
                    @endforelse
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Entrega</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->due_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Prioridad</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->personal_priority ? '#'.$task->personal_priority.' · ' : '' }}{{ $taskPriorityMeta[$task->priority]['label'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Carga diaria</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->planned_for?->translatedFormat('d M Y') ?: 'Sin fecha' }}</div>
                <div class="text-sm font-semibold text-slate-500">{{ \App\Models\Task::formatEstimatedMinutes($task->estimated_minutes) }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Lista</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}</div>
            </div>
        </div>

        <div class="grid gap-7 xl:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)]">
            <section class="panel p-7 xl:p-8">
                <h2 class="text-lg font-semibold text-slate-950">Contexto de la tarea</h2>
                <p class="mt-2 text-sm text-slate-500">Aquí vive el detalle completo. El tablero se queda limpio y rápido; esta vista concentra el trabajo fino.</p>

                <dl class="mt-6 space-y-4 text-sm">
                    <div class="flex justify-between gap-4 border-b border-stone-200 pb-3">
                        <dt class="text-slate-500">Proyecto</dt>
                        <dd class="font-medium text-slate-900">{{ $task->project->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-stone-200 pb-3">
                        <dt class="text-slate-500">Cliente</dt>
                        <dd class="font-medium text-slate-900">{{ $task->project->client->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-stone-200 pb-3">
                        <dt class="text-slate-500">Marca</dt>
                        <dd class="font-medium text-slate-900">{{ $task->project->brand?->name ?: 'Sin marca' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-stone-200 pb-3">
                        <dt class="text-slate-500">ODT</dt>
                        <dd class="font-medium text-slate-900">{{ $task->project->odt_code ?: 'Sin ODT' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-stone-200 pb-3">
                        <dt class="text-slate-500">Estatus</dt>
                        <dd><x-status-badge :value="$task->status" /></dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-3xl bg-stone-50 p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Descripción</h3>
                    @if ($task->description)
                        <div class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{!! \App\Support\LinkedText::render($task->description) !!}</div>
                    @else
                        <p class="mt-3 text-sm leading-7 text-slate-700">Todavía no hay una descripción detallada para esta tarea.</p>
                    @endif
                </div>
            </section>

            <section class="panel p-7 xl:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Lista y seguimiento</h2>
                        <p class="mt-2 text-sm text-slate-500">Aquí puedes cerrar subtareas y ajustar el estado sin sobrecargar la tarjeta del tablero.</p>
                    </div>

                    @if ($canOperateTask)
                    <form method="POST" action="{{ route('tasks.update-status', $task) }}" class="grid w-full gap-3 sm:max-w-sm">
                        @csrf
                        @method('PATCH')

                        <select name="status" class="field mt-0 py-2.5">
                            @foreach ($taskStatuses as $status)
                                <option value="{{ $status }}" @selected($task->status === $status)>{{ $taskStatusMeta[$status]['label'] }}</option>
                            @endforeach
                        </select>

                        <input name="blocked_reason" class="field mt-0" placeholder="Motivo al regresar a Por hacer">
                        <input name="return_reason" class="field mt-0" placeholder="Qué debe corregirse si devuelves">

                        <button class="button-secondary">Guardar estado</button>
                    </form>
                    @endif
                </div>

                @if ($task->status === 'todo' && $task->blocked_reason)
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-900"><strong>Por destrabar:</strong> {{ $task->blocked_reason }}</div>
                @endif

                @if ($task->return_reason)
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"><strong>Última devolución:</strong> {{ $task->return_reason }}</div>
                @endif

                <div class="mt-7 rounded-3xl border border-stone-200 bg-stone-50/80 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Avance de la lista</div>
                        <div class="text-sm font-semibold text-slate-700">
                            {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count ?: 0 }}
                        </div>
                    </div>

                    @php
                        $subtaskProgress = $task->subtasks_count > 0
                            ? (int) round(($task->completed_subtasks_count / $task->subtasks_count) * 100)
                            : 0;
                    @endphp

                    <div class="progress-track">
                        <span class="progress-fill" style="width: {{ $subtaskProgress }}%"></span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($task->subtasks as $subtask)
                            @if ($canOperateTask)
                            <form method="POST" action="{{ route('subtasks.update', $subtask) }}" class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                @csrf
                                @method('PATCH')

                                <input type="hidden" name="is_done" value="{{ $subtask->is_done ? 0 : 1 }}">

                                <button
                                    type="submit"
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold {{ $subtask->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-stone-300 bg-white text-white' }}"
                                    aria-label="{{ $subtask->is_done ? 'Marcar como pendiente' : 'Marcar como lista' }}"
                                >
                                    ✓
                                </button>

                                <span class="text-sm leading-6 {{ $subtask->is_done ? 'text-slate-400 line-through' : 'text-slate-700' }}">
                                    {{ $subtask->title }}
                                </span>
                            </form>
                            @else
                                <div class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold {{ $subtask->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-stone-300 text-transparent' }}">✓</span>
                                    <span class="text-sm leading-6 {{ $subtask->is_done ? 'text-slate-400 line-through' : 'text-slate-700' }}">{{ $subtask->title }}</span>
                                </div>
                            @endif
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-5 text-sm text-slate-500">
                                Esta tarea aún no tiene subtareas.
                            </div>
                        @endforelse
                    </div>

                    @if ($canManageTask)
                    <form method="POST" action="{{ route('tasks.subtasks.store', $task) }}" class="mt-4 flex flex-col gap-2 sm:flex-row">
                        @csrf

                        <input
                            type="text"
                            name="subtask_title"
                            class="field mt-0 px-3 py-2.5"
                            placeholder="Nueva subtarea"
                            required
                        >

                        <button class="button-secondary shrink-0 sm:min-w-[8rem]">Agregar</button>
                    </form>
                    @endif
                </div>
            </section>
        </div>

        <section class="panel p-7 xl:p-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Conversación de la tarea</h2>
                    <p class="mt-1 text-sm text-slate-500">Decisiones, instrucciones y seguimiento en el mismo contexto.</p>
                </div>
                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $task->comments->count() }} recientes</span>
            </div>

            <div class="mt-6 grid gap-3 lg:grid-cols-2">
                @forelse ($task->comments as $comment)
                    <article class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <strong class="text-slate-800">{{ $comment->user->name }}</strong>
                            <time class="text-slate-400">{{ $comment->created_at->diffForHumans() }}</time>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $comment->body }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-stone-300 px-4 py-5 text-sm text-slate-500">Todavía no hay comentarios.</p>
                @endforelse
            </div>

            @if ($canCommentTask)
                <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="mt-6 grid gap-3">
                    @csrf
                    <textarea name="body" rows="3" class="field" placeholder="Deja una actualización, instrucción o decisión…" required></textarea>
                    <div class="flex justify-end"><button class="button-secondary">Comentar</button></div>
                </form>
            @endif
        </section>
        <div class="mt-6">
            @include('activity._timeline', ['recentActivity' => $recentActivity])
        </div>
    </div>
</x-app-layout>
