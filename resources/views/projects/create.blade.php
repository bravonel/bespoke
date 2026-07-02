<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="page-kicker">Nuevo flujo</p>
            <h1 class="page-title">Crear proyecto</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Estamos arrancando con un formulario simple. Luego esto lo conectamos con plantillas, aprobaciones y automatizaciones.</p>
        </div>
    </x-slot>

    <div class="shell">
        <div class="panel p-6 lg:p-8">
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
                        <label class="field-label" for="project-type">Tipo</label>
                        <input id="project-type" name="project_type" class="field" value="{{ old('project_type', 'campana') }}" required>
                    </div>

                    <div>
                        <label class="field-label" for="project-client">Cliente</label>
                        <select id="project-client" name="client_id" class="field" required>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-brand">Marca</label>
                        <select id="project-brand" name="brand_id" class="field">
                            <option value="">Sin marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }} · {{ $brand->client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-owner">Responsable</label>
                        <select id="project-owner" name="owner_id" class="field">
                            <option value="">Asignarme a mí</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-priority">Prioridad</label>
                        <select id="project-priority" name="priority" class="field">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ str($priority)->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-status">Estatus</label>
                        <select id="project-status" name="status" class="field">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-stage">Etapa</label>
                        <select id="project-stage" name="current_stage" class="field">
                            @foreach ($stages as $stage)
                                <option value="{{ $stage }}" @selected(old('current_stage', 'brief') === $stage)>{{ str($stage)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="project-starts-at">Inicio</label>
                        <input id="project-starts-at" type="date" name="starts_at" class="field" value="{{ old('starts_at') }}">
                    </div>

                    <div>
                        <label class="field-label" for="project-due-at">Fecha compromiso</label>
                        <input id="project-due-at" type="date" name="due_at" class="field" value="{{ old('due_at') }}">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="field-label" for="project-description">Descripción</label>
                        <textarea id="project-description" name="description" rows="5" class="field">{{ old('description') }}</textarea>
                    </div>

                    <div class="lg:col-span-2 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('projects.index') }}" class="button-secondary">Cancelar</a>
                        <button class="button-primary">Crear proyecto</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
