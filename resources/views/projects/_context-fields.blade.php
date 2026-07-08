@php
    $project = $project ?? null;
@endphp

<div>
    <label class="field-label" for="{{ $fieldPrefix }}delivery-type">Tipo de entrega</label>
    <select id="{{ $fieldPrefix }}delivery-type" name="delivery_type" class="field">
        <option value="">Por definir</option>
        @foreach ($deliveryTypes as $value => $label)
            <option value="{{ $value }}" @selected(old('delivery_type', $project?->delivery_type) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('delivery_type')" class="mt-2" />
</div>

<div>
    <label class="field-label" for="{{ $fieldPrefix }}target-audience">Público objetivo</label>
    <input id="{{ $fieldPrefix }}target-audience" name="target_audience" class="field" value="{{ old('target_audience', $project?->target_audience) }}">
    <x-input-error :messages="$errors->get('target_audience')" class="mt-2" />
</div>

<div>
    <label class="field-label" for="{{ $fieldPrefix }}material-size">Medida del material</label>
    <input id="{{ $fieldPrefix }}material-size" name="material_size" class="field" value="{{ old('material_size', $project?->material_size) }}" placeholder="Ej. carta, 1080x1080, por proponer">
    <x-input-error :messages="$errors->get('material_size')" class="mt-2" />
</div>

<div class="lg:col-span-2">
    <label class="field-label" for="{{ $fieldPrefix }}legal-requirements">Legales requeridos</label>
    <textarea id="{{ $fieldPrefix }}legal-requirements" name="legal_requirements" rows="3" class="field" placeholder="Registros, claims, textos legales o advertencias que debe incluir.">{{ old('legal_requirements', $project?->legal_requirements) }}</textarea>
    <x-input-error :messages="$errors->get('legal_requirements')" class="mt-2" />
</div>

<div class="lg:col-span-2">
    <label class="field-label" for="{{ $fieldPrefix }}reference-links">Ligas de referencia</label>
    <textarea id="{{ $fieldPrefix }}reference-links" name="reference_links" rows="3" class="field" placeholder="Pega aquí ligas de OneDrive o materiales de referencia, una por línea.">{{ old('reference_links', $project?->reference_links) }}</textarea>
    <x-input-error :messages="$errors->get('reference_links')" class="mt-2" />
</div>
