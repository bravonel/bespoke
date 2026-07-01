<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Base comercial</p>
                <h1 class="page-title">Clientes</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Da de alta laboratorios y cuentas para que proyectos, marcas y revisiones nazcan con contexto claro.</p>
            </div>

            <button type="button" x-on:click="$dispatch('open-modal', 'create-client')" class="button-primary shrink-0">
                + Nuevo cliente
            </button>
        </div>
    </x-slot>

    <div class="shell">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Estatus</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Marcas</th>
                        <th>Proyectos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white">
                    @forelse ($clients as $client)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $client->name }}</div>
                                @if ($client->notes)
                                    <p class="mt-1 text-xs text-slate-500">{{ $client->notes }}</p>
                                @endif
                            </td>
                            <td><x-status-badge :value="$client->status" /></td>
                            <td>
                                <div>{{ $client->primary_contact_name ?: 'Sin contacto' }}</div>
                                @if ($client->primary_contact_email)
                                    <div class="text-xs text-slate-500">{{ $client->primary_contact_email }}</div>
                                @endif
                            </td>
                            <td>{{ $client->primary_contact_phone ?: '—' }}</td>
                            <td>{{ $client->brands_count }}</td>
                            <td>{{ $client->projects_count }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('open-modal', 'edit-client-{{ $client->id }}')"
                                        class="button-secondary py-1.5 text-xs"
                                    >Editar</button>

                                    <form
                                        method="POST"
                                        action="{{ route('clients.destroy', $client) }}"
                                        onsubmit="return confirm('¿Eliminar cliente {{ addslashes($client->name) }}?')"
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
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Aún no hay clientes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modales de edición — FUERA de la tabla para que Alpine los inicialice correctamente --}}
    @foreach ($clients as $client)
        <x-modal name="edit-client-{{ $client->id }}">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Editar cliente</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $client->name }}</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('clients.update', $client) }}" class="grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-name-{{ $client->id }}">Nombre</label>
                        <input id="ec-name-{{ $client->id }}" name="name" class="field" value="{{ $client->name }}" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-status-{{ $client->id }}">Estatus</label>
                        <select id="ec-status-{{ $client->id }}" name="status" class="field">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($client->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="ec-contact-{{ $client->id }}">Contacto principal</label>
                        <input id="ec-contact-{{ $client->id }}" name="primary_contact_name" class="field" value="{{ $client->primary_contact_name }}">
                    </div>

                    <div>
                        <label class="field-label" for="ec-phone-{{ $client->id }}">Teléfono</label>
                        <input id="ec-phone-{{ $client->id }}" name="primary_contact_phone" class="field" value="{{ $client->primary_contact_phone }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-email-{{ $client->id }}">Correo electrónico</label>
                        <input id="ec-email-{{ $client->id }}" type="email" name="primary_contact_email" class="field" value="{{ $client->primary_contact_email }}">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-notes-{{ $client->id }}">Notas</label>
                        <textarea id="ec-notes-{{ $client->id }}" name="notes" rows="3" class="field">{{ $client->notes }}</textarea>
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                        <button class="button-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

    {{-- Crear cliente modal --}}
    <x-modal name="create-client" :show="$errors->any()">
        <div class="modal-header flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Nuevo cliente</h2>
                <p class="mt-1 text-sm text-slate-500">Solo los datos mínimos para no frenar al equipo.</p>
            </div>
            <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('clients.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label class="field-label" for="client-name">Nombre</label>
                    <input id="client-name" name="name" class="field" value="{{ old('name') }}" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="client-status">Estatus</label>
                    <select id="client-status" name="status" class="field">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="primary-contact-name">Contacto principal</label>
                    <input id="primary-contact-name" name="primary_contact_name" class="field" value="{{ old('primary_contact_name') }}">
                </div>

                <div>
                    <label class="field-label" for="primary-contact-phone">Teléfono</label>
                    <input id="primary-contact-phone" name="primary_contact_phone" class="field" value="{{ old('primary_contact_phone') }}">
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="primary-contact-email">Correo electrónico</label>
                    <input id="primary-contact-email" type="email" name="primary_contact_email" class="field" value="{{ old('primary_contact_email') }}">
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="client-notes">Notas</label>
                    <textarea id="client-notes" name="notes" rows="3" class="field">{{ old('notes') }}</textarea>
                </div>

                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                    <button class="button-primary">Guardar cliente</button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
