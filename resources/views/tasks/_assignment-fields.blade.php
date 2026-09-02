@php
    $rows = collect($assignmentRows ?? old('assignments', []))->map(fn ($row) => [
        'user_id' => (string) (data_get($row, 'user_id') ?? ''),
        'role' => (string) (data_get($row, 'role') ?? ''),
        'work_date' => (string) (data_get($row, 'work_date') ?? ''),
        'estimated_hours' => data_get($row, 'estimated_hours') ?? '',
        'personal_priority' => data_get($row, 'personal_priority') ?? '',
    ])->values()->all();
    if ($rows === []) {
        $rows = [['user_id' => '', 'role' => '', 'work_date' => '', 'estimated_hours' => '', 'personal_priority' => '']];
    }
@endphp

<div
    class="space-y-3"
    x-data="{
        assignments: @js($rows),
        add() { this.assignments.push({ user_id: '', role: '', work_date: '', estimated_hours: '', personal_priority: '' }); },
        remove(index) {
            if (this.assignments.length === 1) {
                this.assignments[0] = { user_id: '', role: '', work_date: '', estimated_hours: '', personal_priority: '' };
                return;
            }
            this.assignments.splice(index, 1);
        }
    }"
>
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="field-label">Participantes</div>
            <p class="text-xs text-slate-500">Una tarjeta compartida; horas y orden independientes por persona.</p>
        </div>
        <button type="button" @click="add()" class="button-secondary px-3 py-2 text-xs">+ Participante</button>
    </div>

    <template x-for="(assignment, index) in assignments" :key="index">
        <div class="grid min-w-0 gap-3 rounded-2xl bg-stone-50 p-3 sm:grid-cols-2">
            <div class="min-w-0 sm:col-span-2">
                <label class="field-label" :for="'assignment-user-' + index">Persona</label>
                <select :id="'assignment-user-' + index" :name="`assignments[${index}][user_id]`" x-model="assignment.user_id" class="field mt-0">
                    <option value="">Sin asignar</option>
                    @foreach ($users->groupBy('area') as $area => $areaUsers)
                        <optgroup label="{{ $area ? \App\Support\OperationalLabels::get($area) : 'Sin área' }}">
                            @foreach ($areaUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}{{ $user->puesto ? ' · '.\App\Support\OperationalLabels::get($user->puesto) : '' }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="field-label" :for="'assignment-date-' + index">Día de carga</label>
                <input :id="'assignment-date-' + index" type="date" :name="`assignments[${index}][work_date]`" x-model="assignment.work_date" class="field mt-0">
            </div>
            <div class="min-w-0">
                <label class="field-label" :for="'assignment-hours-' + index">Horas</label>
                <input :id="'assignment-hours-' + index" type="number" min="0" max="24" step="0.25" :name="`assignments[${index}][estimated_hours]`" x-model="assignment.estimated_hours" class="field mt-0">
            </div>
            <div class="min-w-0">
                <label class="field-label" :for="'assignment-priority-' + index">Orden</label>
                <input :id="'assignment-priority-' + index" type="number" min="1" max="999" :name="`assignments[${index}][personal_priority]`" x-model="assignment.personal_priority" class="field mt-0">
            </div>
            <div class="flex min-w-0 items-end sm:justify-end">
                <button type="button" @click="remove(index)" class="button-secondary w-full px-3 py-2.5 text-xs sm:w-auto">Quitar</button>
            </div>
        </div>
    </template>
    <x-input-error :messages="$errors->get('assignments')" class="mt-2" />
</div>
