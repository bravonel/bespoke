<x-app-layout>
    <x-slot name="header">
        <p class="page-kicker">Cuenta</p>
        <h1 class="page-title mt-2">Mi perfil</h1>
        <p class="mt-2 text-sm text-slate-600">Administra tu información, seguridad y cobertura temporal.</p>
    </x-slot>

    <div class="shell space-y-6 max-w-2xl">
        <div class="panel p-7">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="panel p-7">
            <livewire:profile.update-password-form />
        </div>

        <section class="panel overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-stone-200 p-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-700">
                        <x-heroicon-o-arrow-path-rounded-square class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Cobertura temporal</h2>
                        <p class="mt-1 max-w-xl text-sm leading-6 text-slate-600">Reparte cuentas o proyectos entre uno o varios colaboradores. Cada cobertura inicia y termina automáticamente.</p>
                    </div>
                </div>

                <button type="button" data-open-modal="create-coverage" class="button-primary shrink-0 gap-2" @disabled($coverageCandidates->isEmpty())>
                    <x-heroicon-o-plus class="h-4 w-4" aria-hidden="true" />
                    Agregar reemplazo
                </button>
            </div>

            <div class="space-y-5 p-6">
                <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-xs leading-5 text-slate-600">
                    La cobertura permite ver, comentar y avanzar tu trabajo. No comparte contraseña ni transfiere permisos administrativos, actividad global o gestión de colaboradores.
                </div>

                @if ($receivedCoverages->isNotEmpty())
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Trabajo que estás cubriendo</h3>
                        <div class="mt-3 space-y-2">
                            @foreach ($receivedCoverages as $coverage)
                                <div class="flex items-center gap-3 rounded-2xl border border-pink-100 bg-pink-50/50 px-4 py-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-[#e91e8c] shadow-sm">
                                        <x-heroicon-o-user-plus class="h-4 w-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $coverage->owner->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $coverage->starts_on->translatedFormat('d M Y') }} — {{ $coverage->ends_on->translatedFormat('d M Y') }}</p>
                                        <p class="mt-1 truncate text-xs font-medium text-[#c21875]">{{ $coverage->scopeSummary() }}</p>
                                        @if ($coverage->note)
                                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-600">{{ $coverage->note }}</p>
                                        @endif
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $coverage->isEffective() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">{{ $coverage->statusLabel() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Mis coberturas</h3>
                    @if ($createdCoverages->isEmpty())
                        <p class="mt-3 rounded-2xl border border-dashed border-stone-300 px-4 py-5 text-center text-sm text-slate-500">No tienes coberturas programadas.</p>
                    @else
                        <div class="mt-3 divide-y divide-stone-100">
                            @foreach ($createdCoverages as $coverage)
                                @php
                                    $canCancelCoverage = !$coverage->revoked_at && !$coverage->ends_on->isBefore(today());
                                @endphp
                                <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $coverage->delegate->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $coverage->starts_on->translatedFormat('d M Y') }} — {{ $coverage->ends_on->translatedFormat('d M Y') }}</p>
                                        <p class="mt-1 truncate text-xs font-medium text-slate-700">{{ $coverage->scopeSummary() }}</p>
                                        @if ($coverage->note)
                                            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $coverage->note }}</p>
                                        @endif
                                    </div>
                                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $coverage->statusLabel() }}</span>
                                    @if ($canCancelCoverage)
                                        <x-icon-button type="button" label="Cancelar cobertura de {{ $coverage->delegate->name }}" icon="x-mark" size="sm" data-open-modal="cancel-coverage-{{ $coverage->id }}" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="panel p-7">
            <livewire:profile.delete-user-form />
        </div>
    </div>

    <x-modal name="create-coverage" :show="$errors->any() && old('_form') === 'coverage'" max-width="lg" focusable>
        <form method="POST" action="{{ route('coverages.store') }}" x-data="{ scopeMode: @js(old('scope_mode', 'all')) }">
            @csrf
            <input type="hidden" name="_form" value="coverage">

            <div class="border-b border-stone-200 px-6 py-5">
                <p class="page-kicker">Ausencia programada</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-950">Crear cobertura temporal</h2>
                <p class="mt-1 text-sm text-slate-500">Tú mantienes el control y puedes cancelarla en cualquier momento.</p>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label class="field-label" for="delegate_user_id">Quién te cubrirá</label>
                    <select id="delegate_user_id" name="delegate_user_id" class="field mt-2" required autofocus>
                        <option value="">Selecciona un colaborador</option>
                        @foreach ($coverageCandidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('delegate_user_id') == $candidate->id)>{{ $candidate->name }} · {{ $candidate->area ?: 'Sin área' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('delegate_user_id')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="starts_on">Desde</label>
                        <input id="starts_on" name="starts_on" type="date" min="{{ today()->format('Y-m-d') }}" value="{{ old('starts_on', today()->format('Y-m-d')) }}" class="field mt-2" required>
                        <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
                    </div>
                    <div>
                        <label class="field-label" for="ends_on">Hasta</label>
                        <input id="ends_on" name="ends_on" type="date" min="{{ today()->format('Y-m-d') }}" value="{{ old('ends_on') }}" class="field mt-2" required>
                        <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
                    </div>
                </div>

                <fieldset>
                    <legend class="field-label">Qué trabajo cubrirá</legend>
                    <div class="mt-2 grid grid-cols-2 rounded-2xl border border-stone-200 bg-stone-50 p-1">
                        <label class="cursor-pointer rounded-xl px-3 py-2.5 text-center text-xs font-semibold transition" :class="scopeMode === 'all' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'">
                            <input type="radio" name="scope_mode" value="all" class="sr-only" x-model="scopeMode">
                            Todo mi trabajo
                        </label>
                        <label class="cursor-pointer rounded-xl px-3 py-2.5 text-center text-xs font-semibold transition" :class="scopeMode === 'selected' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'">
                            <input type="radio" name="scope_mode" value="selected" class="sr-only" x-model="scopeMode">
                            Elegir alcance
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('scope_mode')" class="mt-2" />

                    <div x-show="scopeMode === 'selected'" class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-stone-200 p-3">
                            <p class="px-1 text-xs font-semibold text-slate-700">Cuentas completas</p>
                            <p class="mt-1 px-1 text-[11px] leading-4 text-slate-400">Incluye todos tus proyectos de la cuenta.</p>
                            <div class="mt-3 max-h-40 space-y-1 overflow-y-auto pr-1">
                                @forelse ($coverageClients as $client)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl px-2 py-2 text-xs text-slate-600 hover:bg-stone-50">
                                        <input type="checkbox" name="client_ids[]" value="{{ $client->id }}" class="rounded border-stone-300 text-[#e91e8c] focus:ring-pink-300" @checked(in_array($client->id, old('client_ids', [])))>
                                        <span class="truncate">{{ $client->name }}</span>
                                    </label>
                                @empty
                                    <p class="px-2 py-3 text-xs text-slate-400">No hay cuentas disponibles.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-2xl border border-stone-200 p-3">
                            <p class="px-1 text-xs font-semibold text-slate-700">Proyectos específicos</p>
                            <p class="mt-1 px-1 text-[11px] leading-4 text-slate-400">Úsalo cuando no quieras delegar la cuenta completa.</p>
                            <div class="mt-3 max-h-40 space-y-1 overflow-y-auto pr-1">
                                @forelse ($coverageProjects as $project)
                                    <label class="flex cursor-pointer items-start gap-2 rounded-xl px-2 py-2 text-xs text-slate-600 hover:bg-stone-50">
                                        <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" class="mt-0.5 rounded border-stone-300 text-[#e91e8c] focus:ring-pink-300" @checked(in_array($project->id, old('project_ids', [])))>
                                        <span class="min-w-0">
                                            <span class="block truncate">{{ $project->name }}</span>
                                            <span class="block truncate text-[10px] text-slate-400">{{ $project->client?->name }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-2 py-3 text-xs text-slate-400">No hay proyectos disponibles.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('client_ids')" class="mt-2" />
                    <x-input-error :messages="$errors->get('project_ids')" class="mt-2" />
                </fieldset>

                <div>
                    <label class="field-label" for="coverage_note">Nota para quien te cubre <span class="font-normal text-slate-400">(opcional)</span></label>
                    <textarea id="coverage_note" name="note" rows="3" maxlength="500" class="field mt-2" placeholder="Ej. Priorizar la entrega del viernes.">{{ old('note') }}</textarea>
                    <x-input-error :messages="$errors->get('note')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-stone-200 bg-stone-50 px-6 py-4">
                <button type="button" class="button-secondary" x-on:click="$dispatch('close')">Cancelar</button>
                <button type="submit" class="button-primary">Guardar reemplazo</button>
            </div>
        </form>
    </x-modal>

    @foreach ($createdCoverages as $coverage)
        @if (!$coverage->revoked_at && !$coverage->ends_on->isBefore(today()))
            <x-modal name="cancel-coverage-{{ $coverage->id }}" max-width="sm" focusable>
                <form method="POST" action="{{ route('coverages.destroy', $coverage) }}">
                    @csrf
                    @method('DELETE')
                    <div class="p-6">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-rose-50 text-rose-600">
                            <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-slate-950">Cancelar cobertura</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $coverage->delegate->name }} dejará de tener acceso temporal a tu trabajo. Esta acción no elimina ninguna tarea ni actividad.</p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-stone-200 bg-stone-50 px-6 py-4">
                        <button type="button" class="button-secondary" x-on:click="$dispatch('close')">Conservar</button>
                        <x-danger-button>Sí, cancelar</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
