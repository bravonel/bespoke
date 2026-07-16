<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="page-kicker">Equipo interno</p>
                <h1 class="page-title">Colaboradores</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Administra altas, bajas, datos de contacto y capacidad diaria del equipo sin perder el historial operativo.</p>
            </div>

            <button type="button" data-open-modal="create-collaborator" class="button-primary shrink-0">
                + Nuevo colaborador
            </button>
        </div>
    </x-slot>

    @php
        $hasFilters = array_filter($filters);
    @endphp

    <datalist id="collaborator-areas">
        @foreach ($areas as $area)
            <option value="{{ $area }}">
        @endforeach
    </datalist>

    <datalist id="collaborator-positions">
        @foreach ($positions as $position)
            <option value="{{ $position }}">
        @endforeach
    </datalist>

    <div class="shell space-y-5">
        <form
            method="GET"
            action="{{ route('collaborators.index') }}"
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
                <input id="f-q" type="text" name="q" class="field mt-0" placeholder="Nombre, correo, área o puesto…" value="{{ $filters['q'] }}" x-on:input="submitSoon($el.form)">
            </div>

            <div class="min-w-[12rem]">
                <label class="field-label" for="f-area">Área</label>
                <select id="f-area" name="area" class="field mt-0" x-on:change="submitForm($el.form)">
                    <option value="">Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area }}" @selected($filters['area'] === $area)>{{ \App\Support\OperationalLabels::get($area) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[10rem]">
                <label class="field-label" for="f-status">Estatus</label>
                <select id="f-status" name="status" class="field mt-0" x-on:change="submitForm($el.form)">
                    <option value="">Todos</option>
                    <option value="active" @selected($filters['status'] === 'active')>Activos</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option>
                </select>
            </div>

            @if ($hasFilters)
                <a href="{{ route('collaborators.index') }}" class="button-secondary">Limpiar</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Área / puesto</th>
                        <th>Estatus</th>
                        <th>Capacidad</th>
                        <th>Carga histórica</th>
                        <th>Actividad</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white">
                    @forelse ($collaborators as $collaborator)
                        <tr class="{{ $collaborator->is_active ? '' : 'bg-stone-50/70' }}">
                            <td>
                                <div class="font-semibold text-slate-900">{{ $collaborator->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $collaborator->email }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">{{ $roleOptions[$collaborator->role] ?? 'Rol pendiente' }}</div>
                                @if ($collaborator->whatsapp_enabled)
                                    <div class="mt-1 text-xs font-semibold text-emerald-700">WhatsApp activo · +{{ $collaborator->whatsapp_phone }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $collaborator->area ? \App\Support\OperationalLabels::get($collaborator->area) : 'Sin área' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $collaborator->puesto ? \App\Support\OperationalLabels::get($collaborator->puesto) : 'Sin puesto' }}</div>
                            </td>
                            <td>
                                <x-status-badge :value="$collaborator->is_active ? 'active' : 'inactive'" />
                            </td>
                            <td>{{ \App\Models\Task::formatEstimatedMinutes($collaborator->daily_capacity_minutes) }} / día</td>
                            <td>
                                <div class="text-sm text-slate-700">{{ $collaborator->assigned_tasks_count }} tareas</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $collaborator->owned_projects_count }} proyectos · {{ $collaborator->project_workloads_count }} cargas</div>
                            </td>
                            <td>
                                <div class="text-sm text-slate-700">{{ $collaborator->lastSeenLabel() }}</div>
                                <div class="mt-1 text-xs text-slate-500">Inicio: {{ $collaborator->lastLoginLabel() }}</div>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        data-open-modal="edit-collaborator-{{ $collaborator->id }}"
                                        class="button-secondary py-1.5 text-xs"
                                    >Editar</button>

                                    @if ($collaborator->is(auth()->user()))
                                        <span class="rounded-xl border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-xs font-semibold text-slate-500">Tu usuario</span>
                                    @elseif ($collaborator->is_active)
                                        <form
                                            method="POST"
                                            action="{{ route('collaborators.deactivate', $collaborator) }}"
                                            onsubmit="return confirm('¿Dar de baja a {{ addslashes($collaborator->name) }}? Ya no podrá iniciar sesión ni aparecerá como opción para nuevas asignaciones.')"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Dar de baja</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('collaborators.activate', $collaborator) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-xl border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Reactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                @if ($hasFilters)
                                    Ningún colaborador coincide con los filtros actuales.
                                @else
                                    Aún no hay colaboradores registrados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($collaborators->hasPages())
            <div>
                {{ $collaborators->links() }}
            </div>
        @endif
    </div>

    @foreach ($collaborators as $collaborator)
        <x-modal name="edit-collaborator-{{ $collaborator->id }}" :show="$errors->any() && old('_form') === 'edit-collaborator-'.$collaborator->id">
            <div class="modal-header flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Editar colaborador</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $collaborator->name }}</p>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('collaborators.update', $collaborator) }}" class="grid gap-5 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="edit-collaborator-{{ $collaborator->id }}">

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-name-{{ $collaborator->id }}">Nombre completo</label>
                        <input id="ec-name-{{ $collaborator->id }}" name="name" class="field" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('name') : $collaborator->name }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-email-{{ $collaborator->id }}">Correo electrónico</label>
                        <input id="ec-email-{{ $collaborator->id }}" type="email" name="email" class="field" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('email') : $collaborator->email }}" required>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="ec-area-{{ $collaborator->id }}">Área</label>
                        <input id="ec-area-{{ $collaborator->id }}" name="area" class="field" list="collaborator-areas" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('area') : $collaborator->area }}">
                        <x-input-error :messages="$errors->get('area')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="ec-puesto-{{ $collaborator->id }}">Puesto</label>
                        <input id="ec-puesto-{{ $collaborator->id }}" name="puesto" class="field" list="collaborator-positions" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('puesto') : $collaborator->puesto }}">
                        <x-input-error :messages="$errors->get('puesto')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="ec-capacity-{{ $collaborator->id }}">Horas disponibles por día</label>
                        <input id="ec-capacity-{{ $collaborator->id }}" type="number" min="0.25" max="24" step="0.25" name="daily_capacity_hours" class="field" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('daily_capacity_hours') : $collaborator->dailyCapacityHoursForInput() }}" required>
                        <x-input-error :messages="$errors->get('daily_capacity_hours')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="ec-role-{{ $collaborator->id }}">Rol de acceso</label>
                        <select id="ec-role-{{ $collaborator->id }}" name="role" class="field">
                            <option value="">Pendiente de asignar</option>
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected((old('_form') === 'edit-collaborator-'.$collaborator->id ? old('role') : $collaborator->role) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label" for="ec-whatsapp-{{ $collaborator->id }}">WhatsApp con código de país</label>
                        <input id="ec-whatsapp-{{ $collaborator->id }}" inputmode="tel" name="whatsapp_phone" class="field" placeholder="5215512345678" value="{{ old('_form') === 'edit-collaborator-'.$collaborator->id ? old('whatsapp_phone') : $collaborator->whatsapp_phone }}">
                        <x-input-error :messages="$errors->get('whatsapp_phone')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-stone-200 px-4 py-3 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="whatsapp_enabled" value="1" class="rounded border-stone-300" @checked(old('_form') === 'edit-collaborator-'.$collaborator->id ? old('whatsapp_enabled') : $collaborator->whatsapp_enabled)>
                        Permitir consultas por WhatsApp
                    </label>

                    <div>
                        <label class="field-label" for="ec-password-{{ $collaborator->id }}">Nueva contraseña</label>
                        <input id="ec-password-{{ $collaborator->id }}" type="password" name="password" class="field" autocomplete="new-password">
                        <p class="mt-1 text-xs text-slate-500">Déjala vacía para conservar la actual.</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="ec-password-confirmation-{{ $collaborator->id }}">Confirmar nueva contraseña</label>
                        <input id="ec-password-confirmation-{{ $collaborator->id }}" type="password" name="password_confirmation" class="field" autocomplete="new-password">
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-3">
                        <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                        <button class="button-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endforeach

    <x-modal name="create-collaborator" :show="$errors->any() && old('_form') === 'create-collaborator'">
        <div class="modal-header flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Nuevo colaborador</h2>
                <p class="mt-1 text-sm text-slate-500">Crea el acceso y deja lista su capacidad diaria para planeación.</p>
            </div>
            <button type="button" x-on:click="$dispatch('close')" class="mt-0.5 shrink-0 text-slate-400 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('collaborators.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="_form" value="create-collaborator">

                <div class="sm:col-span-2">
                    <label class="field-label" for="collaborator-name">Nombre completo</label>
                    <input id="collaborator-name" name="name" class="field" value="{{ old('_form') === 'create-collaborator' ? old('name') : '' }}" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="collaborator-email">Correo electrónico</label>
                    <input id="collaborator-email" type="email" name="email" class="field" value="{{ old('_form') === 'create-collaborator' ? old('email') : '' }}" required>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <label class="field-label" for="collaborator-area">Área</label>
                    <input id="collaborator-area" name="area" class="field" list="collaborator-areas" value="{{ old('_form') === 'create-collaborator' ? old('area') : '' }}">
                    <x-input-error :messages="$errors->get('area')" class="mt-2" />
                </div>

                <div>
                    <label class="field-label" for="collaborator-puesto">Puesto</label>
                    <input id="collaborator-puesto" name="puesto" class="field" list="collaborator-positions" value="{{ old('_form') === 'create-collaborator' ? old('puesto') : '' }}">
                    <x-input-error :messages="$errors->get('puesto')" class="mt-2" />
                </div>

                <div>
                    <label class="field-label" for="collaborator-capacity">Horas disponibles por día</label>
                    <input id="collaborator-capacity" type="number" min="0.25" max="24" step="0.25" name="daily_capacity_hours" class="field" value="{{ old('_form') === 'create-collaborator' ? old('daily_capacity_hours', '8') : '8' }}" required>
                    <x-input-error :messages="$errors->get('daily_capacity_hours')" class="mt-2" />
                </div>

                <div>
                    <label class="field-label" for="collaborator-role">Rol de acceso</label>
                    <select id="collaborator-role" name="role" class="field">
                        <option value="">Pendiente de asignar</option>
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div>
                    <label class="field-label" for="collaborator-whatsapp">WhatsApp con código de país</label>
                    <input id="collaborator-whatsapp" inputmode="tel" name="whatsapp_phone" class="field" placeholder="5215512345678" value="{{ old('_form') === 'create-collaborator' ? old('whatsapp_phone') : '' }}">
                    <x-input-error :messages="$errors->get('whatsapp_phone')" class="mt-2" />
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-stone-200 px-4 py-3 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="whatsapp_enabled" value="1" class="rounded border-stone-300" @checked(old('_form') === 'create-collaborator' && old('whatsapp_enabled'))>
                    Permitir consultas por WhatsApp
                </label>

                <div>
                    <label class="field-label" for="collaborator-password">Contraseña inicial</label>
                    <input id="collaborator-password" type="password" name="password" class="field" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="collaborator-password-confirmation">Confirmar contraseña</label>
                    <input id="collaborator-password-confirmation" type="password" name="password_confirmation" class="field" required autocomplete="new-password">
                </div>

                <div class="sm:col-span-2 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button>
                    <button class="button-primary">Crear colaborador</button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
