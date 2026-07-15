<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Operación diaria</p>
                <h1 class="page-title mt-2">Proyectos</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Aquí empieza el módulo tipo Monday, pero más ligero: responsables claros, siguiente paso visible y tareas conectadas al proyecto.</p>
            </div>

            <button
                type="button"
                x-on:click="$dispatch('open-modal', 'create-project')"
                class="button-primary"
            >
                Nuevo proyecto
            </button>
        </div>
    </x-slot>

    <div class="shell space-y-5">

        {{-- Filtros --}}
        <form
            method="GET"
            action="{{ route('projects.index') }}"
            class="flex flex-wrap items-end gap-3"
            x-data="{
                timer: null,
                submitSoon() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.$el.requestSubmit(), 450);
                },
                submitNow() {
                    this.$el.requestSubmit();
                },
            }"
        >
            <div class="min-w-[12rem] flex-1">
                <label class="field-label" for="f-q">Buscar</label>
                <input id="f-q" type="text" name="q" class="field mt-0" placeholder="Nombre, ODT o código…" value="{{ $filters['q'] ?? '' }}" x-on:input="submitSoon()">
            </div>

            <div class="min-w-[10rem]">
                <label class="field-label" for="f-client">Cliente</label>
                <select id="f-client" name="client_id" class="field mt-0" x-on:change="submitNow()">
                    <option value="">Todos los clientes</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[9rem]">
                <label class="field-label" for="f-status">Estatus</label>
                <select id="f-status" name="status" class="field mt-0" x-on:change="submitNow()">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[9rem]">
                <label class="field-label" for="f-stage">Etapa</label>
                <select id="f-stage" name="stage" class="field mt-0" x-on:change="submitNow()">
                    <option value="">Todas</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage }}" @selected(($filters['stage'] ?? '') === $stage)>{{ \App\Support\OperationalLabels::get($stage) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                @if (array_filter($filters))
                    <a href="{{ route('projects.index') }}" class="button-secondary">Limpiar</a>
                @endif
            </div>
        </form>

        {{-- Tabla --}}
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Cliente / Marca</th>
                        <th>Etapa</th>
                        <th>Estatus</th>
                        <th>Prioridad</th>
                        <th>Responsable</th>
                        <th>Entrega</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white">
                    @forelse ($projects as $project)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $project->name }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">{{ $project->operationalCodeLabel() }}</div>
                                @if ($project->odt_code)
                                    <div class="mt-1 text-[11px] uppercase tracking-[0.16em] text-slate-400">{{ $project->code }}</div>
                                @endif
                                <div class="mt-2 text-xs text-slate-500">{{ $project->tasks_count }} tareas</div>
                            </td>
                            <td>
                                <div>{{ $project->client->name }}</div>
                                <div class="text-xs text-slate-500">{{ $project->brand?->name ?: 'Sin marca' }}</div>
                            </td>
                            <td>{{ \App\Support\OperationalLabels::get($project->current_stage) }}</td>
                            <td><x-status-badge :value="$project->status" /></td>
                            <td>{{ \App\Support\OperationalLabels::get($project->priority) }}</td>
                            <td>{{ $project->owner?->name ?: 'Sin asignar' }}</td>
                            <td>{{ $project->due_at?->translatedFormat('d M Y') ?: 'Sin fecha' }}</td>
                            <td>
                                <a href="{{ route('projects.show', $project) }}" class="button-secondary">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                @if (array_filter($filters))
                                    Ningún proyecto coincide con los filtros actuales.
                                @else
                                    Aún no hay proyectos activos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div>
                {{ $projects->links() }}
            </div>
        @endif
    </div>

    {{-- Crear proyecto modal --}}
    <x-modal name="create-project" :show="$errors->any()" max-width="2xl">
        <div class="modal-header flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Nuevo proyecto</h2>
                <p class="mt-1 text-sm text-slate-500">Conecta un cliente, define responsables y pon una fecha de entrega para que el equipo arranque con orden.</p>
            </div>
            <button type="button" x-on:click="show = false" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="modal-body">
            @if ($clients->isEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Primero registra un cliente y, de ser posible, una marca.
                </div>
            @else
                <form method="POST" action="{{ route('projects.store') }}" class="grid gap-5 lg:grid-cols-2">
                    @csrf

                    <div>
                        <label class="field-label" for="project-name">Nombre del proyecto</label>
                        <input id="project-name" name="name" class="field" value="{{ old('name') }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="project-odt-code">ODT / Orden de compra</label>
                        <input id="project-odt-code" name="odt_code" class="field" value="{{ old('odt_code') }}">
                        <x-input-error :messages="$errors->get('odt_code')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="project-type">Tipo de material</label>
                        <select id="project-type" name="project_type" class="field" required>
                            @foreach ($materialTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('project_type', 'campana') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @include('projects._context-fields', [
                        'project' => null,
                        'fieldPrefix' => 'project-',
                    ])

                    @include('projects._client-brand-fields', [
                        'project' => null,
                        'fieldPrefix' => 'project-',
                    ])

                    <div>
                        <label class="field-label" for="project-owner">Responsable</label>
                        <select id="project-owner" name="owner_id" class="field">
                            <option value="">Asignarme a mí</option>
                            @foreach ($owners->groupBy('area') as $area => $areaOwners)
                                <optgroup label="{{ $area ? \App\Support\OperationalLabels::get($area) : 'Sin área' }}">
                                    @foreach ($areaOwners as $owner)
                                        <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-priority">Prioridad</label>
                        <select id="project-priority" name="priority" class="field">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ \App\Support\OperationalLabels::get($priority) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-status">Estatus</label>
                        <select id="project-status" name="status" class="field">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-stage">Etapa</label>
                        <select id="project-stage" name="current_stage" class="field">
                            @foreach ($stages as $stage)
                                <option value="{{ $stage }}" @selected(old('current_stage', 'brief') === $stage)>{{ \App\Support\OperationalLabels::get($stage) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-starts-at">Fecha de inicio</label>
                        <input id="project-starts-at" type="date" name="starts_at" class="field" value="{{ old('starts_at') }}">
                    </div>

                    <div>
                        <label class="field-label" for="project-due-at">Fecha de entrega</label>
                        <input id="project-due-at" type="date" name="due_at" class="field" value="{{ old('due_at') }}">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="field-label" for="project-description">Descripción</label>
                        <textarea id="project-description" name="description" rows="3" class="field">{{ old('description') }}</textarea>
                    </div>

                    @include('projects._workload-fields', [
                        'project' => null,
                        'people' => $owners,
                        'fieldPrefix' => 'project-',
                    ])

                    <div class="lg:col-span-2 flex justify-end gap-3">
                        <button type="button" x-on:click="show = false" class="button-secondary">Cancelar</button>
                        <button class="button-primary">Crear proyecto</button>
                    </div>
                </form>
            @endif
        </div>
    </x-modal>
</x-app-layout>
