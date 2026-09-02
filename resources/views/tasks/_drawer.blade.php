@php
    $subtaskProgress = $task->subtasks_count > 0
        ? (int) round(($task->completed_subtasks_count / $task->subtasks_count) * 100)
        : 0;
    $isOverdue = ! in_array($task->status, \App\Models\Task::inactiveStatuses(), true) && $task->due_at?->isPast();
@endphp

<div data-drwr>
    {{-- Header --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="page-kicker">{{ $task->project->operationalCodeLabel() }} · {{ $task->project->name }}</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-950" data-view>{{ $task->title }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $task->project->client->name }}
                @if ($task->project->brand)
                    · {{ $task->project->brand->name }}
                @endif
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2 pt-1">
            @if ($canManageTask)
            <x-icon-button
                label="Editar tarea"
                icon="pencil"
                size="sm"
                data-view
                onclick="this.closest('[data-drwr]').classList.add('is-editing')"
            />

            <form
                method="POST"
                action="{{ route('tasks.destroy', $task) }}"
                data-confirm-modal
                data-confirm-tone="danger"
                data-confirm-title="Eliminar tarea"
                data-confirm-message="¿Quieres eliminar “{{ $task->title }}”? Esta acción no se puede deshacer."
                data-confirm-detail="La evidencia de auditoría permanecerá protegida."
                data-confirm-action="Eliminar tarea"
                class="inline"
            >
                @csrf
                @method('DELETE')
                <x-icon-button label="Eliminar tarea" icon="trash" tone="danger" size="sm" type="submit" />
            </form>
            @endif
        </div>
    </div>

    {{-- VIEW mode --}}
    <div data-view>
        {{-- Estatus y datos --}}
        <div class="mb-6 flex flex-wrap items-center gap-3">
            @if ($canOperateTask)
            <form method="POST" action="{{ route('tasks.update-status', $task) }}" class="w-full space-y-3 rounded-2xl border border-stone-200 bg-stone-50/70 p-3">
                @csrf
                @method('PATCH')
                <div class="flex items-center gap-2">
                    <label for="drawer-task-status-{{ $task->id }}" class="sr-only">Estatus de la tarea</label>
                    <select id="drawer-task-status-{{ $task->id }}" name="status" class="field mt-0 min-w-0 flex-1 py-2 text-sm">
                        @foreach ($taskStatuses as $status)
                            <option value="{{ $status }}" @selected($task->status === $status)>
                                {{ $taskStatusMeta[$status]['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <button class="button-secondary gap-2 py-2 text-sm">
                        <x-heroicon-o-check class="h-4 w-4" aria-hidden="true" />
                        Guardar
                    </button>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-medium text-slate-500">Bloqueo, si regresa a Por hacer</span>
                        <input name="blocked_reason" class="field mt-1.5 py-2 text-sm" placeholder="Describe qué impide avanzar">
                    </label>
                    <label class="block">
                        <span class="text-xs font-medium text-slate-500">Corrección, si se devuelve</span>
                        <input name="return_reason" class="field mt-1.5 py-2 text-sm" placeholder="Describe qué debe corregirse">
                    </label>
                </div>
            </form>
            @endif

            <span class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $taskPriorityMeta[$task->priority]['label'] }}
            </span>

            @if ($task->due_at)
                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $isOverdue ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-stone-200 bg-stone-50 text-slate-600' }}">
                    Entrega {{ $task->due_at->translatedFormat('d M Y') }}
                </span>
            @endif

            @forelse ($task->assignments as $assignment)
                <span class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ $assignment->user?->name ?: 'Sin participante' }}
                    · {{ $assignment->work_date?->translatedFormat('d M') ?: 'sin fecha' }}
                    · {{ \App\Models\Task::formatEstimatedMinutes($assignment->estimated_minutes) }}
                    @if ($assignment->personal_priority) · #{{ $assignment->personal_priority }} @endif
                </span>
            @empty
                <span class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-semibold text-slate-600">Sin participantes</span>
            @endforelse

            @if ($canManageTask && ! in_array($task->status, \App\Models\Task::inactiveStatuses(), true))
                @foreach ($task->assignments as $assignment)
                    <form method="POST" action="{{ route('tasks.update-schedule', $task) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="planned_for" value="{{ today()->addDay()->format('Y-m-d') }}">
                        <input type="hidden" name="user_id" value="{{ $assignment->user_id }}">
                        <button class="button-secondary px-3 py-1.5 text-xs">{{ $assignment->user?->name }}: mañana</button>
                    </form>
                @endforeach
            @endif
        </div>

        @if ($task->status === 'todo' && $task->blocked_reason)
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-900">
                <strong>Por destrabar:</strong> {{ $task->blocked_reason }}
            </div>
        @endif

        @if ($task->return_reason)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                <strong>Última devolución:</strong> {{ $task->return_reason }}
            </div>
        @endif

        {{-- Description --}}
        <div class="mb-6 rounded-2xl bg-stone-50 p-5">
            <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Descripción</h3>
            @if ($task->description)
                <div class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{!! \App\Support\LinkedText::render($task->description) !!}</div>
            @else
                <p class="mt-3 text-sm leading-7 text-slate-700">Sin descripción.</p>
            @endif
        </div>

        {{-- Lista --}}
        <div class="mb-8">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lista</h3>
                <span class="text-sm font-semibold text-slate-700" data-drawer-progress-count>
                    {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}
                </span>
            </div>

            <div class="progress-track mt-0 mb-4">
                <span class="progress-fill" data-drawer-progress-bar style="width: {{ $subtaskProgress }}%"></span>
            </div>

            <div class="space-y-2">
                @forelse ($task->subtasks as $subtask)
                    @if ($canOperateTask)
                    <form
                        method="POST"
                        action="{{ route('subtasks.update', $subtask) }}"
                        data-drawer-subtask-form
                        data-subtask-id="{{ $subtask->id }}"
                        class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_done" value="{{ $subtask->is_done ? 0 : 1 }}">
                        <button
                            type="submit"
                            data-subtask-toggle
                            aria-label="{{ $subtask->is_done ? 'Marcar como pendiente' : 'Marcar como lista' }}"
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold {{ $subtask->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-stone-300 bg-white text-transparent' }}"
                        >✓</button>
                        <span data-subtask-title class="text-sm {{ $subtask->is_done ? 'text-slate-400 line-through' : 'text-slate-700' }}">
                            {{ $subtask->title }}
                        </span>
                    </form>
                    @else
                        <div class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold {{ $subtask->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-stone-300 text-transparent' }}">✓</span>
                            <span class="text-sm {{ $subtask->is_done ? 'text-slate-400 line-through' : 'text-slate-700' }}">{{ $subtask->title }}</span>
                        </div>
                    @endif
                @empty
                    <div class="rounded-2xl border border-dashed border-stone-300 px-4 py-4 text-sm text-slate-500">
                        Sin subtareas aún.
                    </div>
                @endforelse
            </div>

            <p class="mt-3 hidden text-sm font-medium text-rose-700" role="alert" data-drawer-checklist-error></p>

            @if ($canManageTask)
            <form method="POST" action="{{ route('tasks.subtasks.store', $task) }}" class="mt-3 flex gap-2">
                @csrf
                <input
                    type="text"
                    name="subtask_title"
                    class="field mt-0 px-3 py-2.5 text-sm"
                    placeholder="Nueva subtarea"
                    required
                >
                <button class="button-secondary shrink-0">Agregar</button>
            </form>
            @endif
        </div>

        <div class="mb-8 border-t border-stone-200 pt-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Conversación</h3>
                <span class="text-xs font-semibold text-slate-400">{{ $task->comments->count() }} recientes</span>
            </div>

            <div class="space-y-3">
                @forelse ($task->comments as $comment)
                    <article class="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <strong class="text-slate-800">{{ $comment->user->name }}</strong>
                            <time class="text-slate-400">{{ $comment->created_at->diffForHumans() }}</time>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $comment->body }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-stone-300 px-4 py-4 text-sm text-slate-500">Sin comentarios todavía.</p>
                @endforelse
            </div>

            @if ($canCommentTask)
                <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="mt-4 space-y-2">
                    @csrf
                    <textarea name="body" rows="3" class="field" placeholder="Deja una actualización, instrucción o decisión…" required></textarea>
                    <div class="flex justify-end"><button class="button-secondary">Comentar</button></div>
                </form>
            @endif
        </div>

        {{-- Link to full detail page --}}
        <div class="border-t border-stone-200 pt-5">
            <a href="{{ route('tasks.show', $task) }}" class="text-sm font-medium hover:underline" style="color:var(--brand-amber)">
                Ver detalle completo →
            </a>
        </div>
    </div>

    {{-- EDIT mode --}}
    @if ($canManageTask)
    <div data-edit>
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="field-label" for="drwr-title">Título</label>
                <input id="drwr-title" name="title" class="field" value="{{ $task->title }}" required>
            </div>

            <div>
                <label class="field-label" for="drwr-description">Descripción</label>
                <textarea id="drwr-description" name="description" rows="4" class="field">{{ $task->description }}</textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="drwr-priority">Prioridad</label>
                    <select id="drwr-priority" name="priority" class="field">
                        @foreach ($taskPriorities as $priority)
                            <option value="{{ $priority }}" @selected($task->priority === $priority)>{{ $taskPriorityMeta[$priority]['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="drwr-due-at">Fecha de entrega</label>
                    <input id="drwr-due-at" type="date" name="due_at" class="field" value="{{ $task->due_at?->format('Y-m-d') }}">
                </div>
            </div>

            @include('tasks._assignment-fields')

            <div class="flex items-center gap-3 border-t border-stone-200 pt-5">
                <button class="button-primary">Guardar cambios</button>
                <button
                    type="button"
                    onclick="this.closest('[data-drwr]').classList.remove('is-editing')"
                    class="button-secondary"
                >Cancelar</button>
            </div>
        </form>
    </div>
    @endif
    <div class="mt-6">
        @if (auth()->user()->isAdmin())
            @include('activity._timeline', ['recentActivity' => $recentActivity])
        @endif
    </div>
</div>

<style>
[data-drwr] [data-edit] { display: none; }
[data-drwr].is-editing [data-view] { display: none; }
[data-drwr].is-editing [data-edit] { display: block; }
</style>
