@php
    $project = $project ?? null;
    $people = $people ?? collect();
    $existingWorkloads = $project?->workloads?->keyBy('role') ?? collect();
@endphp

<div class="lg:col-span-2 rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
    <div class="mb-4">
        <h3 class="text-sm font-semibold text-slate-950">Cargas por responsable</h3>
        <p class="mt-1 text-xs text-slate-500">Estas horas alimentan la carga diaria del dashboard. Usa una fila por rol inicial del proyecto.</p>
    </div>

    <div class="space-y-3">
        @foreach ($workloadRoles as $role => $roleLabel)
            @php
                $existing = $existingWorkloads->get($role);
                $selectedUser = old("workloads.{$role}.user_id", $existing?->user_id);
                $workDate = old("workloads.{$role}.work_date", $existing?->work_date?->format('Y-m-d'));
                $estimatedHours = old(
                    "workloads.{$role}.estimated_hours",
                    $existing?->estimated_minutes !== null ? $existing->estimated_minutes / 60 : ''
                );
                $notes = old("workloads.{$role}.notes", $existing?->notes);
                $taskStatus = old("workloads.{$role}.status", $existing?->task?->status ?? 'todo');
                $personalPriority = old("workloads.{$role}.personal_priority", $existing?->task?->personal_priority);
                $blockedReason = old("workloads.{$role}.blocked_reason", $existing?->task?->blocked_reason);
                $returnReason = old("workloads.{$role}.return_reason");
            @endphp

            <div class="rounded-2xl border border-stone-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-700">{{ $roleLabel }}</div>

                <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="min-w-0">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-user">Responsable {{ $roleLabel }}</label>
                        <select id="{{ $fieldPrefix }}workload-{{ $role }}-user" name="workloads[{{ $role }}][user_id]" class="field mt-0">
                            <option value="">Sin asignar</option>
                            @foreach ($people->groupBy('area') as $area => $areaPeople)
                                <optgroup label="{{ $area ? \App\Support\OperationalLabels::get($area) : 'Sin área' }}">
                                    @foreach ($areaPeople as $person)
                                        <option value="{{ $person->id }}" @selected((string) $selectedUser === (string) $person->id)>{{ $person->name }}{{ $person->puesto ? ' · ' . \App\Support\OperationalLabels::get($person->puesto) : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('workloads.'.$role.'.user_id')" class="mt-2" />
                    </div>

                    <div class="min-w-0">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-date">Día de carga {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-date" type="date" name="workloads[{{ $role }}][work_date]" class="field mt-0" value="{{ $workDate }}">
                        <x-input-error :messages="$errors->get('workloads.'.$role.'.work_date')" class="mt-2" />
                    </div>

                    <div class="min-w-0">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-hours">Horas {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-hours" type="number" min="0" max="24" step="0.25" name="workloads[{{ $role }}][estimated_hours]" class="field mt-0" value="{{ $estimatedHours }}" placeholder="Horas">
                        <x-input-error :messages="$errors->get('workloads.'.$role.'.estimated_hours')" class="mt-2" />
                    </div>

                    <div class="min-w-0 sm:col-span-2 xl:col-span-2">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-notes">Actividad {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-notes" name="workloads[{{ $role }}][notes]" class="field mt-0" value="{{ $notes }}" placeholder="Actividad">
                        <x-input-error :messages="$errors->get('workloads.'.$role.'.notes')" class="mt-2" />
                    </div>

                    <div class="min-w-0">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-status">Estatus {{ $roleLabel }}</label>
                        <select id="{{ $fieldPrefix }}workload-{{ $role }}-status" name="workloads[{{ $role }}][status]" class="field mt-0">
                            @foreach (\App\Models\Task::statusMeta() as $status => $meta)
                                <option value="{{ $status }}" @selected($taskStatus === $status)>
                                    {{ $meta['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('workloads.'.$role.'.status')" class="mt-2" />
                    </div>

                    <div class="min-w-0">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-priority">Prioridad personal {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-priority" type="number" min="1" max="999" name="workloads[{{ $role }}][personal_priority]" class="field mt-0" value="{{ $personalPriority }}" placeholder="Orden personal">
                    </div>

                    <div class="min-w-0 sm:col-span-2">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-blocked">Motivo de bloqueo {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-blocked" name="workloads[{{ $role }}][blocked_reason]" class="field mt-0" value="{{ $blockedReason }}" placeholder="Motivo de bloqueo (obligatorio si aplica)">
                    </div>

                    <div class="min-w-0 sm:col-span-2">
                        <label class="field-label sr-only" for="{{ $fieldPrefix }}workload-{{ $role }}-return">Motivo de devolución {{ $roleLabel }}</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-return" name="workloads[{{ $role }}][return_reason]" class="field mt-0" value="{{ $returnReason }}" placeholder="Qué debe corregirse si se devuelve">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
