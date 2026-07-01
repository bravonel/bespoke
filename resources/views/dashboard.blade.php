<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Bespoke OS</p>
                <h1 class="page-title">Overview operativo</h1>
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

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="panel p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Proyectos a vigilar</h2>
                        <p class="mt-1 text-sm text-slate-500">Ordenados por fecha compromiso para que el seguimiento no se vaya a chat.</p>
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
                                        {{ $project->due_at?->format('d M Y') ?: 'Sin fecha' }}
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
                                <span>{{ $task->due_at?->format('d M Y') ?: 'Sin fecha' }}</span>
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
