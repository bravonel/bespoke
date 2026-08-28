@php
    $project = $project ?? null;
    $people = $people ?? collect();
@endphp

@if ($canManageCapacity ?? false)
<div class="lg:col-span-2 rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
    <div class="mb-4">
        <h3 class="text-sm font-semibold text-slate-950">Cargas por área</h3>
        <p class="mt-1 text-xs text-slate-500">Cada área crea una sola tarjeta compartida. Agrega participantes y captura sus horas por separado.</p>
    </div>

    <div class="space-y-4">
        @foreach ($workloadRoles as $role => $roleLabel)
            @php
                $existingRows = $project?->workloads?->where('role', $role)->values() ?? collect();
                $existingTask = $existingRows->pluck('task')->filter()->first();
                $defaultParticipants = $existingRows->map(fn ($workload) => [
                    'user_id' => (string) ($workload->user_id ?? ''),
                    'work_date' => $workload->work_date?->format('Y-m-d') ?? '',
                    'estimated_hours' => $workload->estimated_minutes === null ? '' : $workload->estimated_minutes / 60,
                    'personal_priority' => $workload->personal_priority ?? '',
                ])->values()->all();
                $participants = old("workloads.{$role}.participants", $defaultParticipants);
                if (empty($participants)) {
                    $participants = [['user_id' => '', 'work_date' => '', 'estimated_hours' => '', 'personal_priority' => '']];
                }
            @endphp

            <div
                class="rounded-2xl border border-stone-200 bg-white p-4"
                x-data="{
                    participants: @js(array_values($participants)),
                    addParticipant() { this.participants.push({ user_id: '', work_date: '', estimated_hours: '', personal_priority: '' }); },
                    removeParticipant(index) {
                        if (this.participants.length === 1) {
                            this.participants[0] = { user_id: '', work_date: '', estimated_hours: '', personal_priority: '' };
                            return;
                        }
                        this.participants.splice(index, 1);
                    }
                }"
            >
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-700">{{ $roleLabel }}</div>
                    <button type="button" @click="addParticipant()" class="button-secondary px-3 py-2 text-xs">+ Participante</button>
                </div>

                <div class="mb-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem]">
                    <div>
                        <label class="field-label" for="{{ $fieldPrefix }}workload-{{ $role }}-activity">Actividad compartida</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-activity" name="workloads[{{ $role }}][activity]" class="field mt-0" value="{{ old("workloads.{$role}.activity", $existingTask?->title) }}" placeholder="Ej. Diseño de primera propuesta">
                    </div>
                    <div>
                        <label class="field-label" for="{{ $fieldPrefix }}workload-{{ $role }}-status">Estatus</label>
                        <select id="{{ $fieldPrefix }}workload-{{ $role }}-status" name="workloads[{{ $role }}][status]" class="field mt-0">
                            @foreach (\App\Models\Task::statusMeta() as $status => $meta)
                                <option value="{{ $status }}" @selected(old("workloads.{$role}.status", $existingTask?->status ?? 'todo') === $status)>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(participant, index) in participants" :key="index">
                        <div class="grid min-w-0 gap-3 rounded-2xl bg-stone-50 p-3 sm:grid-cols-2 xl:grid-cols-[minmax(12rem,1.4fr)_minmax(9rem,1fr)_8rem_8rem_auto]">
                            <div class="min-w-0">
                                <label class="field-label" :for="'{{ $fieldPrefix }}workload-{{ $role }}-user-' + index">Participante</label>
                                <select :id="'{{ $fieldPrefix }}workload-{{ $role }}-user-' + index" :name="`workloads[{{ $role }}][participants][${index}][user_id]`" x-model="participant.user_id" class="field mt-0">
                                    <option value="">Sin asignar</option>
                                    @foreach ($people->groupBy('area') as $area => $areaPeople)
                                        <optgroup label="{{ $area ? \App\Support\OperationalLabels::get($area) : 'Sin área' }}">
                                            @foreach ($areaPeople as $person)
                                                <option value="{{ $person->id }}">{{ $person->name }}{{ $person->puesto ? ' · '.\App\Support\OperationalLabels::get($person->puesto) : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="field-label" :for="'{{ $fieldPrefix }}workload-{{ $role }}-date-' + index">Día de carga</label>
                                <input :id="'{{ $fieldPrefix }}workload-{{ $role }}-date-' + index" type="date" :name="`workloads[{{ $role }}][participants][${index}][work_date]`" x-model="participant.work_date" class="field mt-0">
                            </div>
                            <div>
                                <label class="field-label" :for="'{{ $fieldPrefix }}workload-{{ $role }}-hours-' + index">Horas</label>
                                <input :id="'{{ $fieldPrefix }}workload-{{ $role }}-hours-' + index" type="number" min="0" max="24" step="0.25" :name="`workloads[{{ $role }}][participants][${index}][estimated_hours]`" x-model="participant.estimated_hours" class="field mt-0">
                            </div>
                            <div>
                                <label class="field-label" :for="'{{ $fieldPrefix }}workload-{{ $role }}-priority-' + index">Orden</label>
                                <input :id="'{{ $fieldPrefix }}workload-{{ $role }}-priority-' + index" type="number" min="1" max="999" :name="`workloads[{{ $role }}][participants][${index}][personal_priority]`" x-model="participant.personal_priority" class="field mt-0">
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removeParticipant(index)" class="button-secondary w-full px-3 py-2.5 text-xs xl:w-auto">Quitar</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="{{ $fieldPrefix }}workload-{{ $role }}-blocked">Motivo que impide avanzar</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-blocked" name="workloads[{{ $role }}][blocked_reason]" class="field mt-0" value="{{ old("workloads.{$role}.blocked_reason", $existingTask?->blocked_reason) }}" placeholder="Se conserva al regresar a Por hacer">
                    </div>
                    <div>
                        <label class="field-label" for="{{ $fieldPrefix }}workload-{{ $role }}-return">Motivo de devolución</label>
                        <input id="{{ $fieldPrefix }}workload-{{ $role }}-return" name="workloads[{{ $role }}][return_reason]" class="field mt-0" value="{{ old("workloads.{$role}.return_reason") }}" placeholder="Qué debe corregirse">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
