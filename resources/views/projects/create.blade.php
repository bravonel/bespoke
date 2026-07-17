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
                        <label class="field-label" for="project-odt-code">ODT</label>
                        <input id="project-odt-code" name="odt_code" class="field" value="{{ old('odt_code') }}" required>
                        <x-input-error :messages="$errors->get('odt_code')" class="mt-2" />
                    </div>

                    @include('projects._material-field', [
                        'project' => null,
                        'fieldPrefix' => 'project-',
                    ])

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
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }}</option>
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
                        <textarea id="project-description" name="description" rows="5" class="field">{{ old('description') }}</textarea>
                    </div>

                    @include('projects._workload-fields', [
                        'project' => null,
                        'people' => $owners,
                        'fieldPrefix' => 'project-',
                    ])

                    <div class="lg:col-span-2 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('projects.index') }}" class="button-secondary">Cancelar</a>
                        <button class="button-primary">Crear proyecto</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
