<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">{{ $task->project->code }} · Tarea</p>
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
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card">
                <div class="metric-label">Responsable</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->assignee?->name ?: 'Sin asignar' }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Vence</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $task->due_at?->format('d M Y') ?: 'Sin fecha' }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Prioridad</div>
                <div class="mt-3 text-lg font-semibold text-slate-950">{{ $taskPriorityMeta[$task->priority]['label'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Checklist</div>
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
                        <dt class="text-slate-500">Estatus</dt>
                        <dd><x-status-badge :value="$task->status" /></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Responsable general</dt>
                        <dd class="font-medium text-slate-900">{{ $task->project->owner?->name ?: 'Sin asignar' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-3xl bg-stone-50 p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Descripción</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-700">
                        {{ $task->description ?: 'Todavía no hay una descripción detallada para esta tarea.' }}
                    </p>
                </div>
            </section>

            <section class="panel p-7 xl:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Checklist y seguimiento</h2>
                        <p class="mt-2 text-sm text-slate-500">Aquí puedes cerrar subtareas y ajustar el estado sin sobrecargar la tarjeta del tablero.</p>
                    </div>

                    <form method="POST" action="{{ route('tasks.update-status', $task) }}" class="grid w-full gap-3 sm:max-w-sm">
                        @csrf
                        @method('PATCH')

                        <select name="status" class="field mt-0 py-2.5">
                            @foreach ($taskStatuses as $status)
                                <option value="{{ $status }}" @selected($task->status === $status)>{{ $taskStatusMeta[$status]['label'] }}</option>
                            @endforeach
                        </select>

                        <button class="button-secondary">Guardar estado</button>
                    </form>
                </div>

                <div class="mt-7 rounded-3xl border border-stone-200 bg-stone-50/80 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Avance del checklist</div>
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
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-5 text-sm text-slate-500">
                                Esta tarea aún no tiene subtareas.
                            </div>
                        @endforelse
                    </div>

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
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
