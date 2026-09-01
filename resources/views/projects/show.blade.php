<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">{{ $project->operationalCodeLabel() }}</p>
                <h1 class="page-title mt-2">{{ $project->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ $project->client->name }}
                    @if ($project->brand)
                        · {{ $project->brand->name }}
                    @endif
                    @if ($project->odt_code)
                        · Código interno {{ $project->code }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-status-badge :value="$project->status" />
                <x-status-badge :value="$project->current_stage" />
                @if ($canManageProject)
                    <button type="button" @click="$dispatch('open-modal', 'edit-project')" data-open-modal="edit-project" class="button-secondary gap-2 whitespace-nowrap">
                        <x-heroicon-o-pencil class="h-4 w-4" aria-hidden="true" />
                        Editar
                    </button>
                @endif
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
                if (a && !String(card.dataset.assignee || '').split('|').includes(a)) return false;
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
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-6">
            <div class="metric-card">
                <div class="metric-label">Tareas activas</div>
                <div class="metric-value">{{ $boardSummary['total_tasks'] - $boardSummary['done_tasks'] - $boardSummary['finalized_tasks'] }}</div>
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
                <div class="metric-label">Sin participantes</div>
                <div class="metric-value">{{ $boardSummary['unassigned_tasks'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Horas tareas</div>
                <div class="metric-value">{{ \App\Models\Task::formatEstimatedMinutes($boardSummary['planned_minutes']) }}</div>
                @if ($boardSummary['missing_estimates'] > 0)
                    <div class="text-xs font-semibold text-amber-700">{{ $boardSummary['missing_estimates'] }} sin horas</div>
                @endif
            </div>
        </div>

        {{-- Resumen operativo – compact horizontal strip --}}
        <div class="panel p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resumen operativo</h2>
                    <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">ODT</dt>
                            <dd class="font-medium text-slate-900">{{ $project->odt_code ?: 'Sin ODT' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Participantes</dt>
                            <dd class="font-medium text-slate-900">{{ $project->workloads->pluck('user.name')->filter()->unique()->join(', ') ?: 'Sin asignar' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Prioridad</dt>
                            <dd class="font-medium text-slate-900">{{ \App\Support\OperationalLabels::get($project->priority) }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Fecha de inicio</dt>
                            <dd class="font-medium text-slate-900">{{ $project->starts_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Fecha de entrega</dt>
                            <dd class="font-medium text-slate-900">{{ $project->due_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Tipo de material</dt>
                            <dd class="font-medium text-slate-900">{{ \App\Models\Project::materialTypeLabel($project->project_type) }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Entrega</dt>
                            <dd class="font-medium text-slate-900">{{ $deliveryTypes[$project->delivery_type] ?? 'Por definir' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Público</dt>
                            <dd class="font-medium text-slate-900">{{ $project->target_audience ?: 'Por definir' }}</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <dt class="text-slate-500">Medida</dt>
                            <dd class="font-medium text-slate-900">{{ $project->material_size ?: 'Por definir' }}</dd>
                        </div>
                    </dl>

                    @if ($project->description)
                        <p class="mt-4 whitespace-pre-line break-words text-sm leading-6 text-slate-600">{!! \App\Support\LinkedText::render($project->description) !!}</p>
                    @endif

                    @if ($project->legal_requirements || $project->reference_links)
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            @if ($project->legal_requirements)
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Legales</h3>
                                    <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-600">{!! \App\Support\LinkedText::render($project->legal_requirements) !!}</p>
                                </div>
                            @endif

                            @if ($project->reference_links)
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Referencias</h3>
                                    <div class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-600">{!! \App\Support\LinkedText::render($project->reference_links) !!}</div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($collaboratorLoadRows->isNotEmpty())
            <section class="panel p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-950">Horas por colaborador</h2>
                    <p class="mt-1 text-sm text-slate-500">Suma de tareas abiertas y cargas iniciales asignadas dentro de este proyecto.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">Colaborador</th>
                                <th class="py-2 pr-4 font-semibold">Rol / área</th>
                                <th class="py-2 pr-4 font-semibold">Tareas abiertas</th>
                                <th class="py-2 pr-4 font-semibold">Cargas iniciales</th>
                                <th class="py-2 pr-4 font-semibold">Total</th>
                                <th class="py-2 font-semibold">Sin horas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($collaboratorLoadRows as $row)
                                @php $person = $row['user']; @endphp
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">{{ $person?->name ?: 'Sin asignar' }}</td>
                                    <td class="py-3 pr-4 text-slate-600">
                                        @if ($person)
                                            <div>{{ $person->area ? \App\Support\OperationalLabels::get($person->area) : 'Sin área' }}</div>
                                            @if ($person->puesto)
                                                <div class="text-xs text-slate-400">{{ \App\Support\OperationalLabels::get($person->puesto) }}</div>
                                            @endif
                                        @elseif ($row['roles']->isNotEmpty())
                                            {{ $row['roles']->join(', ') }}
                                        @else
                                            Sin área
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-slate-600">
                                        <span class="font-semibold text-slate-900">{{ \App\Models\Task::formatEstimatedMinutes($row['task_minutes']) }}</span>
                                        <span class="text-xs text-slate-400">· {{ $row['task_count'] }} tareas</span>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-600">
                                        <span class="font-semibold text-slate-900">{{ \App\Models\Task::formatEstimatedMinutes($row['workload_minutes']) }}</span>
                                        <span class="text-xs text-slate-400">· {{ $row['workload_count'] }} cargas</span>
                                    </td>
                                    <td class="py-3 pr-4 font-semibold text-slate-950">{{ \App\Models\Task::formatEstimatedMinutes($row['total_minutes']) }}</td>
                                    <td class="py-3">
                                        @if ($row['missing_estimates'] > 0)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $row['missing_estimates'] }}</span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($project->workloads->isNotEmpty())
            <section class="panel p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-slate-950">Cargas por participante</h2>
                    <p class="mt-1 text-sm text-slate-500">Horas iniciales asignadas por rol para alimentar la carga diaria.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">Rol</th>
                                <th class="py-2 pr-4 font-semibold">Participante</th>
                                <th class="py-2 pr-4 font-semibold">Día de carga</th>
                                <th class="py-2 pr-4 font-semibold">Horas</th>
                                <th class="py-2 font-semibold">Actividad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($project->workloads->sortBy('work_date') as $workload)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-slate-900">{{ $workloadRoles[$workload->role] ?? $workload->role }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $workload->user?->name ?: 'Sin asignar' }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ $workload->work_date?->translatedFormat('d M Y') ?: 'Sin fecha' }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ \App\Models\Task::formatEstimatedMinutes($workload->estimated_minutes) }}</td>
                                    <td class="py-3 text-slate-600">{{ $workload->notes ?: 'Sin detalle' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Board --}}
        <section class="panel p-7 xl:p-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Tablero del proyecto</h2>
                    <p class="mt-1 max-w-3xl text-sm text-slate-500">En computadora puedes arrastrar tarjetas entre columnas. Para editar descripción o seguimiento fino, abre la tarea.</p>
                </div>

                <div class="flex flex-wrap shrink-0 items-center gap-2">
                    <select
                        x-model="boardFilter.assignee"
                        class="field mt-0 py-2 text-sm min-w-[9rem]"
                    >
                        <option value="">Todos los participantes</option>
                        @foreach ($users->groupBy('area') as $area => $areaUsers)
                            <optgroup label="{{ $area ? \App\Support\OperationalLabels::get($area) : 'Sin área' }}">
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
                        {{ $boardSummary['done_tasks'] + $boardSummary['finalized_tasks'] }}/{{ $boardSummary['total_tasks'] }} entregadas o finalizadas
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
                                    $isOverdue = ! in_array($task->status, \App\Models\Task::inactiveStatuses(), true) && $task->due_at?->isPast();
                                    $cardContext = $task->status === 'todo'
                                        ? ($task->blocked_reason ?: $task->return_reason)
                                        : null;
                                    $cardDate = $task->due_at ?: $task->planned_for;
                                    $cardDateLabel = $task->due_at ? 'Entrega' : 'Carga';
                                @endphp

                                <article
                                    class="task-card task-card--compact"
                                    draggable="{{ $canManageProject || in_array(auth()->id(), $task->assignedUserIds(), true) ? 'true' : 'false' }}"
                                    data-task-card
                                    data-task-id="{{ $task->id }}"
                                    data-move-url="{{ route('tasks.move', $task) }}"
                                    data-detail-url="{{ route('tasks.show', $task) }}"
                                    data-assignee="{{ $task->assignments->pluck('user.name')->filter()->unique()->join('|') ?: ($task->assignee?->name ?? '') }}"
                                    data-priority="{{ $task->priority }}"
                                >
                                    <div class="flex items-start gap-2.5">
                                        <div class="min-w-0 flex-1">
                                            <button type="button" class="task-card__link text-left" data-open-task aria-label="Abrir tarea {{ $task->title }}">
                                                <p class="truncate text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                                                    {{ $task->assignments->pluck('user.name')->filter()->unique()->join(', ') ?: ($task->assignee?->name ?: 'Sin asignar') }}
                                                </p>
                                                <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-slate-950">{{ $task->title }}</h3>

                                                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[11px] font-semibold text-slate-500">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $task->priority === 'high' ? 'bg-rose-500' : ($task->priority === 'low' ? 'bg-sky-400' : 'bg-amber-400') }}"></span>
                                                        {{ $taskPriorityMeta[$task->priority]['label'] }}
                                                    </span>
                                                    @if ($cardDate)
                                                        <span class="inline-flex items-center gap-1 {{ $isOverdue ? 'text-rose-700' : '' }}" data-tooltip="{{ $cardDateLabel }}: {{ $cardDate->translatedFormat('d M Y') }}">
                                                            <x-heroicon-o-calendar-days class="h-3.5 w-3.5" aria-hidden="true" />
                                                            {{ $cardDate->translatedFormat('d M') }}
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex items-center gap-1">
                                                        <x-heroicon-o-clock class="h-3.5 w-3.5" aria-hidden="true" />
                                                        {{ \App\Models\Task::formatEstimatedMinutes($task->estimated_minutes) }}
                                                    </span>
                                                    @if ($task->subtasks_count > 0)
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-check-circle class="h-3.5 w-3.5" aria-hidden="true" />
                                                            {{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}
                                                        </span>
                                                    @endif
                                                    @if ($task->personal_priority)
                                                        <span class="rounded-full bg-slate-950 px-2 py-0.5 text-[9px] font-bold text-white">#{{ $task->personal_priority }}</span>
                                                    @endif
                                                </div>
                                                @if ($cardContext)
                                                    <p class="mt-2.5 line-clamp-2 rounded-xl border border-rose-100 bg-rose-50 px-2.5 py-2 text-[11px] leading-4 text-rose-800">
                                                        <span class="font-semibold">{{ $task->blocked_reason ? 'Por destrabar:' : 'Corrección:' }}</span>
                                                        {{ $cardContext }}
                                                    </p>
                                                @endif
                                            </button>
                                        </div>

                                        <div class="flex shrink-0 flex-col items-end gap-2">
                                            @if ($canManageProject || in_array(auth()->id(), $task->assignedUserIds(), true))
                                                <span class="drag-handle hidden h-9 w-9 items-center justify-center px-0 md:inline-flex" aria-label="Arrastrar tarea" data-tooltip="Arrastrar tarea">
                                                    <x-heroicon-o-bars-3 class="h-4 w-4" aria-hidden="true" />
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($task->subtasks_count > 0)
                                        <div class="mt-3">
                                            <div class="progress-track mt-0 h-1.5">
                                                <span class="progress-fill" style="width: {{ $subtaskProgress }}%"></span>
                                            </div>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        {{-- Per-column add button --}}
                        @if ($canManageProject && ! in_array($status, \App\Models\Task::inactiveStatuses(), true))
                            <button
                                type="button"
                                @click="openTaskModal('{{ $status }}')"
                                class="mt-3 w-full rounded-2xl border border-dashed border-stone-300 px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 transition hover:border-[#F5A623]/50 hover:text-[#F5A623]"
                            >
                                + Agregar
                            </button>
                        @endif
                    </section>
                @endforeach
            </div>
        </section>

        {{-- Editar proyecto modal --}}
        @if ($canManageProject)
        <x-modal name="edit-project" max-width="4xl">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Editar proyecto</h2>
                    <p class="mt-1 text-sm text-slate-500">Actualiza los datos del proyecto.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" data-close-modal="edit-project" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('projects.update', $project) }}" class="grid gap-5 lg:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    @include('projects._client-brand-fields', [
                        'project' => $project,
                        'fieldPrefix' => 'ep-',
                    ])

                    <div>
                        <label class="field-label" for="ep-odt-code">ODT</label>
                        <input id="ep-odt-code" name="odt_code" class="field" value="{{ old('odt_code', $project->odt_code) }}" required>
                        <x-input-error :messages="$errors->get('odt_code')" class="mt-2" />
                    </div>

                    @include('projects._material-field', [
                        'project' => $project,
                        'fieldPrefix' => 'ep-',
                    ])

                    @include('projects._context-fields', [
                        'project' => $project,
                        'fieldPrefix' => 'ep-',
                    ])

                    <div>
                        <label class="field-label" for="ep-status">Estatus</label>
                        <select id="ep-status" name="status" class="field">
                            @foreach ($projectStatuses as $status)
                                <option value="{{ $status }}" @selected($project->status === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-priority">Prioridad</label>
                        <select id="ep-priority" name="priority" class="field">
                            @foreach ($projectPriorities as $priority)
                                <option value="{{ $priority }}" @selected($project->priority === $priority)>{{ \App\Support\OperationalLabels::get($priority) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-stage">Etapa actual</label>
                        <select id="ep-stage" name="current_stage" class="field">
                            @foreach ($projectStages as $stage)
                                <option value="{{ $stage }}" @selected($project->current_stage === $stage)>{{ \App\Support\OperationalLabels::get($stage) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ep-starts-at">Fecha de inicio</label>
                        <input id="ep-starts-at" type="date" name="starts_at" class="field" value="{{ $project->starts_at?->format('Y-m-d') }}">
                    </div>

                    <div>
                        <label class="field-label" for="ep-due-at">Fecha de entrega</label>
                        <input id="ep-due-at" type="date" name="due_at" class="field" value="{{ $project->due_at?->format('Y-m-d') }}">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="field-label" for="ep-description">Descripción</label>
                        <textarea id="ep-description" name="description" rows="3" class="field">{{ $project->description }}</textarea>
                    </div>

                    @include('projects._workload-fields', [
                        'project' => $project,
                        'people' => $users,
                        'fieldPrefix' => 'ep-',
                    ])

                    <div class="lg:col-span-2 flex items-center justify-between gap-3">
                        @if ($project->status === 'archived')
                            <button
                                type="submit"
                                form="restore-project-form"
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                            >
                                Restaurar proyecto
                            </button>
                        @else
                            <button
                                type="submit"
                                form="archive-project-form"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100"
                            >
                                Archivar proyecto
                            </button>
                        @endif

                        <div class="flex gap-3">
                            <button type="button" x-on:click="$dispatch('close')" data-close-modal="edit-project" class="button-secondary">Cancelar</button>
                            <button class="button-primary">Guardar cambios</button>
                        </div>
                    </div>
                </form>

                <form
                    id="archive-project-form"
                    method="POST"
                    action="{{ route('projects.archive', $project) }}"
                    data-confirm-modal
                    data-confirm-tone="warning"
                    data-confirm-title="Archivar {{ $project->name }}"
                    data-confirm-message="El proyecto dejará de aparecer en la operación activa."
                    data-confirm-detail="Sus tareas y su historial se conservarán."
                    data-confirm-action="Archivar proyecto"
                >
                    @csrf
                    @method('PATCH')
                </form>
                <form id="restore-project-form" method="POST" action="{{ route('projects.restore', $project) }}">
                    @csrf
                    @method('PATCH')
                </form>
            </div>
        </x-modal>
        @endif

        {{-- Nueva tarea modal --}}
        @if ($canManageProject)
        <div
            x-show="taskModal"
            x-on:keydown.escape.window="if (!window.bespokeDialogIsOpen) taskModal = false"
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
                        <p class="mt-1 text-sm text-slate-500">Crea el pendiente con participantes, fechas y lista de pendientes desde el arranque para que el tablero nazca ordenado.</p>
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
                            <label class="field-label" for="task-due-at">Fecha de entrega</label>
                            <input id="task-due-at" type="date" name="due_at" class="field" value="{{ old('due_at') }}">
                        </div>

                        <div>
                            <label class="field-label" for="task-status">Estatus</label>
                            <select id="task-status" name="status" class="field" x-model="taskStatus">
                                @foreach ($taskStatuses as $status)
                                    @if (! in_array($status, \App\Models\Task::inactiveStatuses(), true))
                                        <option value="{{ $status }}">{{ $taskStatusMeta[$status]['label'] }}</option>
                                    @endif
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
                            @include('tasks._assignment-fields', [
                                'assignmentRows' => old('assignments', []),
                                'users' => $users,
                            ])
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
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">Tip: si ya conoces la lista de pendientes, cárgala aquí y el equipo arranca con orden.</p>
                        </div>

                        <div class="lg:col-span-2 flex justify-end gap-3">
                            <button type="button" @click="taskModal = false" class="button-secondary">Cancelar</button>
                            <button class="button-primary">Agregar tarea</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
        <div class="mt-6">
            @include('activity._timeline', ['recentActivity' => $recentActivity, 'project' => $project])
        </div>
    </div>
</x-app-layout>
