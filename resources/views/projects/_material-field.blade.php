@php
    $project = $project ?? null;
    $selectedMaterial = old('project_type', $project?->project_type ?? 'campana');
    $isCustomMaterial = $selectedMaterial !== '' && ! array_key_exists($selectedMaterial, $materialTypes);
    $materialChoice = $isCustomMaterial ? 'otro' : $selectedMaterial;
    $customMaterial = old('project_type_other', $isCustomMaterial ? $selectedMaterial : '');
@endphp

<div
    x-data="{ materialChoice: @js($materialChoice) }"
    class="space-y-3"
>
    <div>
        <label class="field-label" for="{{ $fieldPrefix }}type">Tipo de material</label>
        <select id="{{ $fieldPrefix }}type" name="project_type" class="field" x-model="materialChoice" required>
            @foreach ($materialTypes as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="materialChoice === 'otro'" x-cloak>
        <label class="field-label" for="{{ $fieldPrefix }}type-other">Especifica el material</label>
        <input
            id="{{ $fieldPrefix }}type-other"
            name="project_type_other"
            class="field"
            value="{{ $customMaterial }}"
            placeholder="Escribe el tipo de material"
            :required="materialChoice === 'otro'"
        >
        <x-input-error :messages="$errors->get('project_type_other')" class="mt-2" />
    </div>
</div>
