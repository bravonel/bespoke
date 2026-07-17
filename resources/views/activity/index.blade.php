<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Trazabilidad</p>
                <h1 class="page-title mt-2">{{ $canViewTeam ? 'Centro de actividad' : 'Mi actividad' }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Evidencia operativa para aclarar cuándo ingresó una persona, cuándo interactuó y cuándo realizó un cambio comprobable.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('activity.export', request()->query()) }}" class="button-secondary" data-activity="report.exported" data-activity-target="activity-csv">Exportar CSV</a>
                <a href="{{ route('activity.print', request()->query()) }}" target="_blank" class="button-secondary" data-activity="report.exported" data-activity-target="activity-pdf">Vista para PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="shell space-y-8">
        <section class="panel p-6">
            <form method="GET" action="{{ route('activity.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div>
                    <label class="field-label" for="activity-from">Desde</label>
                    <input id="activity-from" type="date" name="from" class="field" value="{{ $filters['from'] }}">
                </div>
                <div>
                    <label class="field-label" for="activity-to">Hasta</label>
                    <input id="activity-to" type="date" name="to" class="field" value="{{ $filters['to'] }}">
                </div>
                @if ($canViewTeam)
                    <div>
                        <label class="field-label" for="activity-actor">Colaborador</label>
                        <select id="activity-actor" name="actor_id" class="field">
                            <option value="">Todos</option>
                            @foreach ($users as $person)
                                <option value="{{ $person->id }}" @selected($filters['actor_id'] == $person->id)>{{ $person->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="field-label" for="activity-project">Proyecto</label>
                    <select id="activity-project" name="project_id" class="field">
                        <option value="">Todos</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected($filters['project_id'] == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="activity-event">Evento</label>
                    <select id="activity-event" name="event_type" class="field">
                        <option value="">Todos</option>
                        @foreach ($eventTypes as $eventType)
                            <option value="{{ $eventType }}" @selected($filters['event_type'] === $eventType)>{{ $labels::get($eventType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="activity-channel">Canal</label>
                    <select id="activity-channel" name="channel" class="field">
                        <option value="">Todos</option>
                        @foreach (['web' => 'Web', 'whatsapp' => 'WhatsApp', 'api' => 'API', 'system' => 'Sistema'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['channel'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2 xl:col-span-6">
                    <button class="button-primary">Aplicar filtros</button>
                    <a href="{{ route('activity.index') }}" class="button-secondary">Limpiar</a>
                </div>
            </form>
        </section>

        @if ($canViewTeam)
            <section class="panel overflow-hidden">
                <div class="border-b border-stone-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-slate-950">Seguimiento por colaborador</h2>
                    <p class="mt-1 max-w-3xl text-sm text-slate-500">La interacción acredita presencia en Bespoke OS. Para comprobar que un trabajo se realizó, consulta el último cambio confirmado y la bitácora.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-[0.14em] text-slate-400">
                            <tr>
                                <th class="px-5 py-3">Colaborador</th>
                                <th class="px-5 py-3">Última interacción</th>
                                <th class="px-5 py-3">Último cambio confirmado</th>
                                <th class="px-5 py-3">Último inicio</th>
                                <th class="px-5 py-3 text-right">Evidencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($teamOverview as $person)
                                @php
                                    $lastInteraction = $person->monitor_last_interaction_at;
                                    $lastChange = $person->monitor_last_confirmed_change_at;
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $person->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $person->area ? \App\Support\OperationalLabels::get($person->area) : 'Sin área' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($lastInteraction)
                                            <div class="font-semibold text-slate-800">{{ $lastInteraction->translatedFormat('d M Y · H:i:s') }}</div>
                                            <div class="text-xs text-slate-400">{{ $lastInteraction->diffForHumans() }}</div>
                                        @else
                                            <span class="text-slate-400">Sin interacción registrada</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($lastChange)
                                            <div class="font-semibold text-slate-800">{{ $lastChange->translatedFormat('d M Y · H:i:s') }}</div>
                                            <div class="text-xs text-slate-400">{{ $lastChange->diffForHumans() }}</div>
                                        @else
                                            <span class="text-slate-400">Sin cambios confirmados</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                        @if ($person->last_login_at)
                                            <div>{{ $person->last_login_at->translatedFormat('d M Y · H:i:s') }}</div>
                                            <div class="text-xs text-slate-400">{{ $person->last_login_at->diffForHumans() }}</div>
                                        @else
                                            <span class="text-slate-400">Sin inicio registrado</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('activity.index', ['actor_id' => $person->id, 'from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()]) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Ver evidencia</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card"><div class="metric-label">Eventos</div><div class="metric-value">{{ $summary['events'] }}</div></div>
            <div class="metric-card"><div class="metric-label">Cambios confirmados</div><div class="metric-value">{{ $summary['changes'] }}</div></div>
            <div class="metric-card"><div class="metric-label">Fallidos</div><div class="metric-value">{{ $summary['failed'] }}</div></div>
            <div class="metric-card"><div class="metric-label">Sesiones</div><div class="metric-value">{{ $summary['sessions'] }}</div></div>
        </div>

        @if ($alerts->isNotEmpty())
            <section class="panel border border-amber-200 bg-amber-50/70 p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-950">Alertas abiertas</h2>
                    <p class="mt-1 text-sm text-slate-600">Patrones que requieren revisión humana.</p>
                </div>
                <div class="space-y-3">
                    @foreach ($alerts as $alert)
                        <div class="rounded-2xl border border-amber-200 bg-white px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold text-slate-900">{{ $alert->title }}</div>
                                <div class="text-xs font-semibold uppercase text-amber-700">{{ $alert->severity }}</div>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-slate-600">{{ $alert->description }}</p>
                                <form method="POST" action="{{ route('activity.alerts.resolve', $alert) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs font-semibold text-amber-800 hover:text-amber-950">Marcar resuelta</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="panel overflow-hidden">
            <div class="border-b border-stone-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-950">Bitácora operativa</h2>
                <p class="mt-1 text-sm text-slate-500">Evidencia append-only generada por el servidor.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Actor</th>
                            <th class="px-5 py-3">Evento</th>
                            <th class="px-5 py-3">Contexto</th>
                            <th class="px-5 py-3">Canal</th>
                            <th class="px-5 py-3">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($events as $event)
                            @php
                                $changes = $event->metadata['changes'] ?? [];
                                $entityLabel = $event->auditable?->title
                                    ?? $event->auditable?->name
                                    ?? $event->auditable?->code;
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                    <div>{{ $event->created_at?->translatedFormat('d M Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $event->created_at?->format('H:i:s') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $event->actor?->name ?: 'Sistema' }}</div>
                                    @if ($event->actor?->area)<div class="text-xs text-slate-400">{{ $event->actor->area }}</div>@endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $labels::get($event->event_type) }}</div>
                                    <div class="mt-1 font-mono text-[11px] text-slate-400">{{ $event->event_type }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ $event->project?->name ?: $event->client?->name ?: 'General' }}</div>
                                    @if ($event->auditable_type)
                                        <div class="text-xs text-slate-400">{{ class_basename($event->auditable_type) }} #{{ $event->auditable_id }}{{ $entityLabel ? ' · '.$entityLabel : '' }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4"><span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ strtoupper($event->channel) }}</span></td>
                                <td class="px-5 py-4">
                                    @if ($changes)
                                        <details class="max-w-md">
                                            <summary class="cursor-pointer font-semibold text-amber-700">Ver cambios</summary>
                                            <div class="mt-2 space-y-2">
                                                @foreach ($changes as $field => $change)
                                                    <div class="rounded-xl bg-stone-50 px-3 py-2 text-xs">
                                                        <div class="font-semibold text-slate-700">{{ $field }}</div>
                                                        @if (($change['changed'] ?? false) === true)
                                                            <div class="text-slate-500">Contenido modificado; valor omitido por privacidad.</div>
                                                        @else
                                                            <div class="break-words text-slate-500">{{ json_encode($change['before'] ?? null, JSON_UNESCAPED_UNICODE) }} → {{ json_encode($change['after'] ?? null, JSON_UNESCAPED_UNICODE) }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-slate-400">Sin cambios de campos</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No hay eventos para estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($events->hasPages())<div class="border-t border-stone-200 px-6 py-4">{{ $events->links() }}</div>@endif
        </section>

        <div class="grid gap-7 xl:grid-cols-2">
            <section class="panel p-6">
                <h2 class="text-lg font-semibold text-slate-950">Sesiones recientes</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($sessions as $session)
                        <div class="rounded-2xl border border-stone-200 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div><div class="font-semibold text-slate-900">{{ $session->user?->name }}</div><div class="text-xs text-slate-400">{{ $session->browser }} · {{ $session->platform }} · {{ $session->device_type }}</div></div>
                                <div class="text-right text-xs text-slate-500"><div>{{ $session->started_at?->translatedFormat('d M H:i') }}</div><div>{{ $session->ended_at ? 'Cerrada: '.$session->end_reason : 'Activa' }}</div></div>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">Activo {{ gmdate('H:i:s', $session->active_seconds) }} · Inactivo {{ gmdate('H:i:s', $session->idle_seconds) }} · {{ $session->last_page }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sin sesiones registradas.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel p-6">
                <h2 class="text-lg font-semibold text-slate-950">Interacciones de interfaz</h2>
                <p class="mt-1 text-sm text-slate-500">Sólo controles funcionales; no se capturan teclas ni contenido sin guardar.</p>
                <div class="mt-5 space-y-3">
                    @forelse ($uiEvents as $uiEvent)
                        <div class="flex items-start justify-between gap-4 border-b border-stone-100 pb-3">
                            <div><div class="font-semibold text-slate-800">{{ $labels::get($uiEvent->event_name) }}</div><div class="text-xs text-slate-400">{{ $uiEvent->user?->name }} · {{ $uiEvent->target ?: $uiEvent->page }}</div></div>
                            <div class="whitespace-nowrap text-xs text-slate-400">{{ $uiEvent->occurred_at?->format('d M Y · H:i:s') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sin interacciones registradas.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
