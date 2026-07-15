<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Contexto por cuenta</p>
                <h1 class="page-title">Marcas</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Organiza las marcas por cliente para que cada proyecto y cada expediente científico vivan en el lugar correcto.</p>
            </div>

            @if ($clients->isNotEmpty())
                <button type="button" x-on:click="$dispatch('open-modal', 'create-brand')" class="button-primary shrink-0">
                    + Nueva marca
                </button>
            @endif
        </div>
    </x-slot>

    <div class="shell space-y-5">
        @if ($clients->isEmpty())
            <div class="panel border border-amber-200 bg-amber-50/90 px-6 py-5 text-sm text-amber-800 mb-6">
                Primero crea al menos un cliente para poder registrar marcas.
            </div>
        @endif

        <form
            method="GET"
            action="{{ route('brands.index') }}"
            class="flex flex-wrap items-end gap-3"
            x-data="{
                timer: null,
                submitForm(form) {
                    if (!form) return;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }
                    form.submit();
                },
                submitSoon(form) {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.submitForm(form), 450);
                },
            }"
        >
            <div class="min-w-[13rem] flex-1">
                <label class="field-label" for="f-q">Buscar</label>
                <input id="f-q" type="text" name="q" class="field mt-0" placeholder="Marca, cliente o área…" value="{{ $filters['q'] ?? '' }}" x-on:input="submitSoon($el.form)">
            </div>

            <div class="min-w-[12rem]">
                <label class="field-label" for="f-client">Cliente</label>
                <select id="f-client" name="client_id" class="field mt-0" x-on:change="submitForm($el.form)">
                    <option value="">Todos los clientes</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[9rem]">
                <label class="field-label" for="f-status">Estatus</label>
                <select id="f-status" name="status" class="field mt-0" x-on:change="submitForm($el.form)">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                @if (array_filter($filters))
                    <a href="{{ route('brands.index') }}" class="button-secondary">Limpiar</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Cliente</th>
                        <th>Área terapéutica</th>
                        <th>Estatus</th>
                        <th>Proyectos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white">
                    @forelse ($brands as $brand)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $brand->name }}</div>
                                @if ($brand->notes)
                                    <p class="mt-1 text-xs text-slate-500">{{ $brand->notes }}</p>
                                @endif
                            </td>
                            <td>{{ $brand->client->name }}</td>
                            <td>{{ $brand->therapeutic_area ?: 'Sin definir' }}</td>
                            <td><x-status-badge :value="$brand->status" /></td>
                            <td>{{ $brand->projects_count }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('open-modal', 'edit-brand-{{ $brand->id }}')"
                                        class="button-secondary py-1.5 text-xs"
                                    >Editar</button>

                                    <form
                                        method="POST"
                                        action="{{ route('brands.destroy', $brand) }}"
                                        onsubmit="return confirm('¿Eliminar marca {{ addslashes($brand->name) }}?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                @if (array_filter($filters))
                                    Ninguna marca coincide con los filtros actuales.
                                @else
                                    Aún no hay marcas registradas.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($brands->hasPages())
            <div>
                {{ $brands->links() }}
            </div>
        @endif
    </div>

    {{-- Modales de edición — FUERA de la tabla --}}
    @foreach ($brands as $brand)
        <x-modal name="edit-brand-{{ $brand->id }}">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Editar marca</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $brand->name }}</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('brands.update', $brand) }}" class="grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div class="sm:col-span-2">
                        <label class="field-label" for="eb-client-{{ $brand->id }}">Cliente</label>
                        <select id="eb-client-{{ $brand->id }}" name="client_id" class="field" required>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected($brand->client_id == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="eb-name-{{ $brand->id }}">Nombre</label>
                        <input id="eb-name-{{ $brand->id }}" name="name" class="field" value="{{ $brand->name }}" required>
                    </div>

                    <div>
                        <label class="field-label" for="eb-area-{{ $brand->id }}">Área terapéutica</label>
                        <input id="eb-area-{{ $brand->id }}" name="therapeutic_area" class="field" value="{{ $brand->therapeutic_area }}">
                    </div>

                    <div>
                        <label class="field-label" for="eb-status-{{ $brand->id }}">Estatus</label>
                        <select id="eb-status-{{ $brand->id }}" name="status" class="field">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($brand->status === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="eb-notes-{{ $brand->id }}">Notas</label>
                        <textarea id="eb-notes-{{ $brand->id }}" name="notes" rows="3" class="field">{{ $brand->notes }}</textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                        <button class="button-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

    @if ($clients->isNotEmpty())
        <x-modal name="create-brand" :show="$errors->any()">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Nueva marca</h2>
                    <p class="mt-1 text-sm text-slate-500">Cada marca queda lista para colgarle proyectos y revisiones más adelante.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('brands.store') }}" class="grid gap-5 sm:grid-cols-2">
                    @csrf

                    <div class="sm:col-span-2">
                        <label class="field-label" for="brand-client">Cliente</label>
                        <select id="brand-client" name="client_id" class="field" required>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="brand-name">Nombre</label>
                        <input id="brand-name" name="name" class="field" value="{{ old('name') }}" required autofocus>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="therapeutic-area">Área terapéutica</label>
                        <input id="therapeutic-area" name="therapeutic_area" class="field" value="{{ old('therapeutic_area') }}">
                    </div>

                    <div>
                        <label class="field-label" for="brand-status">Estatus</label>
                        <select id="brand-status" name="status" class="field">
                            @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ \App\Support\OperationalLabels::get($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="brand-notes">Notas</label>
                        <textarea id="brand-notes" name="notes" rows="3" class="field">{{ old('notes') }}</textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                        <button class="button-primary">Guardar marca</button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif
</x-app-layout>
