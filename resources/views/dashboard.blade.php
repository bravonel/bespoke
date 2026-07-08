<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Bespoke OS</p>
                <h1 class="page-title">Resumen operativo</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Una vista rápida del sistema para que siempre sepamos dónde vamos y qué necesita empuje hoy.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.create') }}" class="button-primary">Nuevo proyecto</a>
                <a href="{{ route('projects.index') }}" class="button-secondary">Ver proyectos</a>
            </div>
        </div>
    </x-slot>

    <div class="shell space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="metric-card">
                <div class="metric-label">Clientes activos</div>
                <div class="metric-value">{{ $summary['clients'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Marcas</div>
                <div class="metric-value">{{ $summary['brands'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Proyectos totales</div>
                <div class="metric-value">{{ $summary['projects'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Proyectos en marcha</div>
                <div class="metric-value">{{ $summary['active_projects'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Tareas abiertas</div>
                <div class="metric-value">{{ $summary['open_tasks'] }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Mis pendientes</div>
                <div class="metric-value">{{ $summary['my_tasks'] }}</div>
            </div>
        </div>

        <section class="panel p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Carga diaria</h2>
                    <div class="mt-1 text-sm text-slate-500">{{ $selectedDate->translatedFormat('d M Y') }}</div>
                </div>

                <form method="GET" action="{{ route('dashboard') }}" class="flex w-full flex-wrap items-end gap-x-3 gap-y-5 lg:w-auto lg:justify-end">
                    <div class="w-full sm:w-40">
                        <label class="field-label block" for="daily-date">Fecha</label>
                        <input id="daily-date" type="date" name="date" class="field mt-1.5 py-2.5" value="{{ $selectedDate->format('Y-m-d') }}">
                    </div>

                    <div class="w-full sm:w-40">
                        <label class="field-label block" for="daily-area">Área</label>
                        <select id="daily-area" name="area" class="field mt-1.5 py-2.5">
                            <option value="">Todas</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area }}" @selected($dailyFilters['area'] === $area)>{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-72">
                        <label class="field-label block" for="daily-user">Usuario</label>
                        <select id="daily-user" name="user_id" class="field mt-1.5 py-2.5">
                            <option value="">Todos</option>
                            @foreach ($users->groupBy('area') as $area => $areaUsers)
                                <optgroup label="{{ $area ?: 'Sin área' }}">
                                    @foreach ($areaUsers as $user)
                                        <option value="{{ $user->id }}" @selected($dailyFilters['user_id'] === $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <button class="button-primary h-12 w-full sm:w-auto">Aplicar</button>
                </form>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <div class="metric-label">Actividades</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $dailySummary['tasks'] }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <div class="metric-label">Horas</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ \App\Models\Task::formatEstimatedMinutes($dailySummary['estimated_minutes']) }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <div class="metric-label">Bloqueadas</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $dailySummary['blocked'] }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <div class="metric-label">Vencidas</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $dailySummary['overdue'] }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <div class="metric-label">Sobrecarga</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $dailySummary['over_capacity_users'] }}</div>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($dailyLoadRows as $row)
                    @php
                        $assignee = $row['assignee'];
                        $overCapacity = $row['estimated_minutes'] > $row['capacity_minutes'];
                    @endphp

                    <div class="rounded-2xl border border-stone-200 bg-white p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-950">{{ $assignee?->name ?: 'Sin asignar' }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $assignee?->area ?: 'Sin área' }}
                                    @if ($assignee?->puesto)
                                        · {{ $assignee->puesto }}
                                    @endif
                                </div>
                            </div>

                            <div class="grid gap-2 text-sm sm:grid-cols-4 lg:min-w-[34rem]">
                                <div class="rounded-xl bg-stone-50 px-3 py-2">
                                    <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Actividades</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $row['task_count'] }}</div>
                                </div>
                                <div class="rounded-xl bg-stone-50 px-3 py-2">
                                    <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Carga</div>
                                    <div class="mt-1 font-semibold {{ $overCapacity ? 'text-rose-700' : 'text-slate-900' }}">
                                        {{ \App\Models\Task::formatEstimatedMinutes($row['estimated_minutes']) }} / {{ \App\Models\Task::formatEstimatedMinutes($row['capacity_minutes']) }}
                                    </div>
                                </div>
                                <div class="rounded-xl bg-stone-50 px-3 py-2">
                                    <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Bloqueadas</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $row['blocked_count'] }}</div>
                                </div>
                                <div class="rounded-xl bg-stone-50 px-3 py-2">
                                    <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Sin horas</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $row['missing_estimate_count'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-200">
                            <span class="block h-full rounded-full {{ $overCapacity ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $row['capacity_percent'] }}%"></span>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-xs uppercase tracking-[0.16em] text-slate-400">
                                    <tr>
                                        <th class="py-2 pr-4 font-semibold">Actividad</th>
                                        <th class="py-2 pr-4 font-semibold">Proyecto</th>
                                        <th class="py-2 pr-4 font-semibold">ODT</th>
                                        <th class="py-2 pr-4 font-semibold">Tipo</th>
                                        <th class="py-2 pr-4 font-semibold">Horas</th>
                                        <th class="py-2 pr-4 font-semibold">Entrega</th>
                                        <th class="py-2 font-semibold"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-100">
                                    @foreach ($row['activities'] as $activity)
                                        @php
                                            $project = $activity['project'];
                                            $task = $activity['task'] ?? null;
                                        @endphp
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-slate-900">
                                                <div>{{ $activity['title'] }}</div>
                                                @if ($activity['role'])
                                                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ $activity['role'] }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">
                                                <div>{{ $project->name }}</div>
                                                <div class="text-xs text-slate-400">
                                                    {{ $project->client->name }}
                                                    @if ($project->brand)
                                                        · {{ $project->brand->name }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $project->odt_code ?: 'Sin ODT' }}</td>
                                            <td class="py-3 pr-4">
                                                @if ($task)
                                                    <x-status-badge :value="$task->status" />
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700 ring-1 ring-inset ring-stone-200">Carga</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ \App\Models\Task::formatEstimatedMinutes($activity['estimated_minutes']) }}</td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $activity['due_at']?->translatedFormat('d M Y') ?: 'Sin fecha' }}</td>
                                            <td class="py-3 text-right">
                                                @if ($task && $task->status !== 'done')
                                                    <form method="POST" action="{{ route('tasks.update-schedule', $task) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="planned_for" value="{{ today()->addDay()->format('Y-m-d') }}">
                                                        <button class="button-secondary px-3 py-1.5 text-xs">Pasar a mañana</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-5 text-sm text-slate-500">
                        No hay actividades planeadas para esta fecha.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="panel p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Proyectos a vigilar</h2>
                        <p class="mt-1 text-sm text-slate-500">Ordenados por fecha de entrega para que el seguimiento no se vaya a chat.</p>
                    </div>

                    <a href="{{ route('projects.index') }}" class="button-secondary">Todos</a>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($projectsDueSoon as $project)
                        <a href="{{ route('projects.show', $project) }}" class="block rounded-2xl border border-stone-200 bg-stone-50/90 p-4 transition hover:border-stone-300 hover:bg-white">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $project->name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $project->client->name }}
                                        @if ($project->brand)
                                            · {{ $project->brand->name }}
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <x-status-badge :value="$project->status" />
                                        <x-status-badge :value="$project->current_stage" />
                                    </div>
                                </div>

                                <div class="text-sm text-slate-600">
                                    <div>{{ $project->owner?->name ?: 'Sin responsable' }}</div>
                                    <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                                        {{ $project->due_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl bg-stone-50 p-5 text-sm text-slate-500">Todavía no hay proyectos para mostrar.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel p-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Actividad reciente</h2>
                    <p class="mt-1 text-sm text-slate-500">Primer acercamiento a la vista de pendientes y movimiento real del equipo.</p>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($recentTasks as $task)
                        <div class="rounded-2xl border border-stone-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $task->title }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $task->project->name }}</div>
                                </div>

                                <x-status-badge :value="$task->status" />
                            </div>

                            <div class="mt-3 flex flex-wrap gap-4 text-xs uppercase tracking-[0.18em] text-slate-400">
                                <span>{{ $task->assignee?->name ?: 'Sin asignar' }}</span>
                                <span>{{ $task->due_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-stone-50 p-5 text-sm text-slate-500">Agrega tareas a un proyecto y aquí empezaremos a ver el ritmo operativo.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
