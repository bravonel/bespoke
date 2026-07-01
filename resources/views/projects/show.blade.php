<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">{{ $project->code }}</p>
                <h1 class="page-title mt-2">{{ $project->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ $project->client->name }}
                    @if ($project->brand)
                        · {{ $project->brand->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-status-badge :value="$project->status" />
                <x-status-badge :value="$project->current_stage" />
                <button type="button" @click="$dispatch('open-modal', 'edit-project')" class="button-secondary">Editar proyecto</button>
                <a href="{{ route('projects.index') }}" class="button-secondary">Volver</a>
            </div>
        </div>
    </x-slot>

    <div
        class="shell space-y-10"
        x-data="{
            taskModal: {{ $errors->any() ? 'true' : 'false' }},
            taskStatus: '{{ old('status', 'todo') }}',
            boardFilter: { assignee: '', priority: '' },
            openTaskModal(status) {
                this.taskStatus = status;
                this.taskModal = true;
            },
            cardVisible(card) {
                const a = this.boardFilter.assignee;
                const p = this.boardFilter.priority;
                if (a && card.dataset.assignee !== a) return false;
                if (p && card.dataset.priority !== p) return false;
                return true;
            },
            applyBoardFilter() {
                document.querySelectorAll('[data-task-card]').forEach(card => {
                    card.style.display = this.cardVisible(card) ? '' : 'none';
                });
                document.querySelectorAll('[data-board-column]').forEach(col => {
                    const visible = [...col.querySelectorAll('[data-task-card]')].filter(c => c.style.display !== 'none');
                    const empty = col.querySelector('[data-empty-state]');
                    if (empty) empty.classList.toggle('hidden', visible.length > 0);
                    const counter = col.querySelector('[data-column-count]');
                    if (counter) counter.textContent = visible.length;
                });
            }
        }"
        x-init="$watch('boardFilter', () => applyBoardFilter())"
    >
        {{-- Metric cards --}}
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-5">
            <div class="metric-card">
                <div class="metric-label">Tareas activas</div>
                <div class="metric-value">{{ $boardSummary['total_tasks'] - $boardSummary['done_tasks'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Avance</div>
                <div class="metric-value">{{ $boardSummary['completion_rate'] }}%</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Subtareas abiertas</div>
                <div class="metric-value">{{ $boardSummary['open_subtasks'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Vencidas</div>
                <div class="metric-value">{{ $boardSummary['overdue_tasks'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Sin responsable</div>
                <div class="metric-value">{{ $boardSummary['unassigned_tasks'] }}</div>
            </div>
        </div>

        {{-- Resumen operativo – compact horizontal strip --}}
        <div class="panel p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resumen operativo</h2>
                    <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Responsable</dt>
                            <dd class="font-medium text-slate-900">{{ $project->owner?->name ?: 'Sin asignar' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Prioridad</dt>
                            <dd class="font-medium text-slate-900">{{ str($project->priority)->title() }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Inicio</dt>
                            <dd class="font-medium text-slate-900">{{ $project->starts_at?->format('d M Y') ?: 'Sin fecha' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Fecha compromiso</dt>
                            <dd class="font-medium text-slate-900">{{ $project->due_at?->format('d M Y') ?: 'Sin fecha' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Tipo</dt>
                            <dd class="font-medium text-slate-900">{{ str($project->project_type)->title() }}</dd>
                        </div>
                    </dl>

                    @if ($project->description)
                        <p class="mt-4 max-w-3xl text-sm text-slate-600">{{ $project->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Board --}}
        <section class="panel p-7 xl:p-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Tablero del proyecto</h2>
                    <p class="mt-1 max-w-3xl text-sm text-slate-500">En desktop puedes arrastrar tarjetas entre columnas. Para editar descripción o seguimiento fino, abre la tarea.</p>
                </div>

                <div class="flex flex-wrap shrink-0 items-center gap-2">
                    <select
                        x-model="boardFilter.assignee"
                        class="field mt-0 py-2 text-sm min-w-[9rem]"
                    >
                        <option value="">Todos los responsables</option>
                        @foreach ($users->groupBy('area') as $area => $areaUsers)
                            <optgroup label="{{ $area ?: 'Sin área' }}">
                                @foreach ($areaUsers as $user)
                                    <option value="{{ $user->name }}">{{ $user->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <select
                        x-model="boardFilter.priority"
                        class="field mt-0 py-2 text-sm min-w-[8rem]"
                    >
                        <option value="">Toda prioridad</option>
                        @foreach ($taskPriorities as $priority)
                            <option value="{{ $priority }}">{{ $taskPriorityMeta[$priority]['label'] }}</option>
                        @endforeach
                    </select>

                    <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-xs uppercase tracking-[0.18em] text-slate-500">
                        {{ $boardSummary['done_tasks'] }}/{{ $boardSummary['total_tasks'] }} listas
                    </div>

                    <button
                        type="button"
                        @click="openTaskModal('todo')"
                        class="button-primary"
                    >
                        + Nueva tarea
                    </button>
                </div>
            </div>

            <div class="mt-7 board-grid" data-task-board>
                @foreach ($taskStatusMeta as $status => $meta)
                    <section class="board-column" data-board-column data-status="{{ $status }}">
                        <div class="board-column__header">
                            <div>
                                <h3 class="text-base font-semibold text-slate-950">{{ $meta['label'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $meta['description'] }}</p>
                            </div>

                            <span class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-2xl bg-stone-100 px-3 text-sm font-semibold text-slate-700" data-column-count>
                                {{ $taskGroups[$status]->count() }}
                            </span>
                        </div>

                        <div class="board-list" data-column-list>
                            <div data-empty-state class="{{ $taskGroups[$status]->isNotEmpty() ? 'hidden ' : '' }}rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-5 text-sm text-slate-500">
                                Sin tarjetas en esta columna.
                            </div>

                            @foreach ($taskGroups[$status] as $task)
                                @php
                                    $subtaskProgress = $task->subtasks_count > 0
                                        ? (int) round(($task->completed_subtasks_count / $task->subtasks_count) * 100)
                                        : 0;
                                    $isOverdue = $task->status !== 'done' && $task->due_at?->isPast();
                                    $openSubtasksCount = $task->subtasks_count - $task->completed_subtasks_count;
                                @endphp

                                <article
                                    class="task-card task-card--compact"
                                    draggable="true"
                                    data-task-card
                                    data-task-id="{{ $task->id }}"
                                    data-move-url="{{ route('tasks.move', $task) }}"
                                    data-detail-url="{{ route('tasks.show', $task) }}"
                                    data-assignee="{{ $task->assignee?->name ?? '' }}"
                                    data-priority="{{ $task->priority }}"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="min-w-0 flex-1">
                                            <button type="button" class="task-card__link text-left" data-open-task>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                    {{ $task->assignee?->name ?: 'Sin asignar' }}
                                                </p>
                                                <h3 class="mt-1.5 text-sm font-semibold text-slate-950">{{ $task->title }}</h3>

                                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold">
                                                    <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-slate-600">
                                                        {{ $taskPriorityMeta[$task->priority]['label'] }}
                                                    </span>
                                                    <span class="rounded-full border px-2.5 py-1 {{ $isOverdue ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-stone-200 bg-stone-50 text-slate-500' }}">
                                                        {{ $task->due_at?->format('d M Y') ?: 'Sin fecha' }}
                                                    </span>
                                                    <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-slate-500">
                                                        {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }} checklist
                                                    </span>
                                                    @if ($openSubtasksCount > 0)
                                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-amber-700">
                                                            {{ $openSubtasksCount }} pendiente{{ $openSubtasksCount > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </button>
                                        </div>

                                        <div class="flex shrink-0 flex-col items-end gap-2">
                                            <span class="drag-handle hidden md:inline-flex">Arrastrar</span>
                                        </div>
                                    </div>

                                    @if ($task->subtasks_count > 0)
                                        <div class="mt-4">
                                            <div class="progress-track mt-0">
                                                <span class="progress-fill" style="width: {{ $subtaskProgress }}%"></span>
                                            </div>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        {{-- Per-column add button --}}
                        <button
                            type="button"
                            @click="openTaskModal('{{ $status }}')"
                            class="mt-3 w-full rounded-2xl border border-dashed border-stone-300 px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 transition hover:border-[#F5A623]/50 hover:text-[#F5A623]"
                        >
                            + Agregar
                        </button>
                    </section>
                @endforeach
            </div>
        </section>

        {{-- Editar proyecto modal --}}
        <x-modal name="edit-project">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Editar proyecto</h2>
                    <p class="mt-1 text-sm text-slate-500">Actualiza los datos del proyecto.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('projects.update', $project) }}" class="grid gap-5 lg:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="field-label" for="ep-client">Cliente</label>
                        <select id="ep-client" name="client_id" class="field" required>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected($project->client_id == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-brand">Marca</label>
                        <select id="ep-brand" name="brand_id" class="field">
                            <option value="">Sin marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected($project->brand_id == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="field-label" for="ep-name">Nombre del proyecto</label>
                        <input id="ep-name" name="name" class="field" value="{{ $project->name }}" required>
                    </div>

                    <div>
                        <label class="field-label" for="ep-type">Tipo de proyecto</label>
                        <input id="ep-type" name="project_type" class="field" value="{{ $project->project_type }}" required>
                    </div>

                    <div>
                        <label class="field-label" for="ep-owner">Responsable</label>
                        <select id="ep-owner" name="owner_id" class="field">
                            <option value="">Sin asignar</option>
                            @foreach ($users->groupBy('area') as $area => $areaUsers)
                                <optgroup label="{{ $area ?: 'Sin área' }}">
                                    @foreach ($areaUsers as $user)
                                        <option value="{{ $user->id }}" @selected($project->owner_id == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-status">Estatus</label>
                        <select id="ep-status" name="status" class="field">
                            @foreach ($projectStatuses as $status)
                                <option value="{{ $status }}" @selected($project->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-priority">Prioridad</label>
                        <select id="ep-priority" name="priority" class="field">
                            @foreach ($projectPriorities as $priority)
                                <option value="{{ $priority }}" @selected($project->priority === $priority)>{{ str($priority)->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-stage">Etapa actual</label>
                        <select id="ep-stage" name="current_stage" class="field">
                            @foreach ($projectStages as $stage)
                                <option value="{{ $stage }}" @selected($project->current_stage === $stage)>{{ str($stage)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-starts-at">Inicio</label>
                        <input id="ep-starts-at" type="date" name="starts_at" class="field" value="{{ $project->starts_at?->format('Y-m-d') }}">
                    </div>

                    <div>
                        <label class="field-label" for="ep-due-at">Fecha compromiso</label>
                        <input id="ep-due-at" type="date" name="due_at" class="field" value="{{ $project->due_at?->format('Y-m-d') }}">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="field-label" for="ep-description">Descripción</label>
                        <textarea id="ep-description" name="description" rows="3" class="field">{{ $project->description }}</textarea>
                    </div>

                    <div class="lg:col-span-2 flex items-center justify-between gap-3">
                        <form
                            method="POST"
                            action="{{ route('projects.destroy', $project) }}"
                            onsubmit="return confirm('¿Eliminar el proyecto {{ addslashes($project->name) }} y todas sus tareas? Esta acción no se puede deshacer.')"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                Eliminar proyecto
                            </button>
                        </form>

                        <div class="flex gap-3">
                            <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                            <button class="button-primary">Guardar cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </x-modal>

        {{-- Nueva tarea modal --}}
        <div
            x-show="taskModal"
            x-on:keydown.escape.window="taskModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-0"
            style="display:none"
        >
            <div
                class="fixed inset-0"
                x-on:click="taskModal = false"
                x-show="taskModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>
            </div>

            <div
                class="relative w-full sm:max-w-2xl overflow-hidden rounded-3xl border border-white/60 bg-white shadow-[0_32px_80px_-24px_rgba(15,23,42,0.45)]"
                x-show="taskModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            >
                <div class="modal-header flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Nueva tarea</h2>
                        <p class="mt-1 text-sm text-slate-500">Crea el pendiente con responsable, fecha y checklist desde el arranque para que el tablero nazca ordenado.</p>
                    </div>
                    <button type="button" @click="taskModal = false" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="modal-body">
                    <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="grid gap-5 lg:grid-cols-2">
                        @csrf

                        <div class="lg:col-span-2">
                            <label class="field-label" for="task-title">Título</label>
                            <input id="task-title" name="title" class="field" value="{{ old('title') }}" required>
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="lg:col-span-2">
                            <label class="field-label" for="task-description">Descripción</label>
                            <textarea id="task-description" name="description" rows="3" class="field">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="field-label" for="task-assigned-to">Asignado a</label>
                            <select id="task-assigned-to" name="assigned_to" class="field">
                                <option value="">Sin asignar</option>
                                @foreach ($users->groupBy('area') as $area => $areaUsers)
                                    <optgroup label="{{ $area ?: 'Sin área' }}">
                                        @foreach ($areaUsers as $user)
                                            <option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>{{ $user->name }}{{ $user->puesto ? ' · ' . $user->puesto : '' }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="field-label" for="task-due-at">Fecha compromiso</label>
                            <input id="task-due-at" type="date" name="due_at" class="field" value="{{ old('due_at') }}">
                        </div>

                        <div>
                            <label class="field-label" for="task-status">Estatus</label>
                            <select id="task-status" name="status" class="field" x-model="taskStatus">
                                @foreach ($taskStatuses as $status)
                                    <option value="{{ $status }}">{{ $taskStatusMeta[$status]['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="field-label" for="task-priority">Prioridad</label>
                            <select id="task-priority" name="priority" class="field">
                                @foreach ($taskPriorities as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ $taskPriorityMeta[$priority]['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="field-label" for="task-subtasks">Subtareas</label>
                            <textarea
                                id="task-subtasks"
                                name="subtasks"
                                rows="4"
                                class="field"
                                placeholder="Una subtarea por línea. Ejemplo:
Revisar brief médico
Preparar primera propuesta
Mandar a cliente"
                            >{{ old('subtasks') }}</textarea>
                            <x-input-error :messages="$errors->get('subtasks')" class="mt-2" />
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">Tip: si ya conoces el checklist, cárgalo aquí y el equipo arranca con orden.</p>
                        </div>

                        <div class="lg:col-span-2 flex justify-end gap-3">
                            <button type="button" @click="taskModal = false" class="button-secondary">Cancelar</button>
                            <button class="button-primary">Agregar tarea</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
