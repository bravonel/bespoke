@php
    $selectedClientId = (string) old('client_id', $project?->client_id ?? '');
    $selectedBrandId = (string) old('brand_id', $project?->brand_id ?? '');
    $clientFieldId = $fieldPrefix.'client';
    $brandFieldId = $fieldPrefix.'brand';
    $brandOptions = $brands
        ->map(fn ($brand) => [
            'id' => (string) $brand->id,
            'client_id' => (string) $brand->client_id,
            'name' => $brand->name,
        ])
        ->values();
@endphp

<div
    class="contents"
    x-data="{
        clientId: @js($selectedClientId),
        brandId: @js($selectedBrandId),
        brands: @js($brandOptions),
        init() {
            this.clientId = String(this.clientId ?? '');
            this.brandId = String(this.brandId ?? '');
            this.ensureBrandBelongsToClient();

            this.$watch('clientId', () => {
                this.clientId = String(this.clientId ?? '');
                this.ensureBrandBelongsToClient();
            });
        },
        get filteredBrands() {
            if (!this.clientId) {
                return [];
            }

            return this.brands.filter((brand) => String(brand.client_id) === this.clientId);
        },
        get brandPlaceholder() {
            if (!this.clientId) {
                return 'Primero selecciona un cliente';
            }

            if (this.filteredBrands.length === 0) {
                return 'Sin marcas para este cliente';
            }

            return 'Sin marca';
        },
        ensureBrandBelongsToClient() {
            if (!this.brandId) {
                return;
            }

            if (!this.filteredBrands.some((brand) => String(brand.id) === this.brandId)) {
                this.brandId = '';
            }
        },
    }"
    x-init="init()"
>
    <div>
        <label class="field-label" for="{{ $clientFieldId }}">Cliente</label>
        <select id="{{ $clientFieldId }}" name="client_id" class="field" x-model="clientId" required>
            <option value="">Selecciona un cliente</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
    </div>

    <div>
        <label class="field-label" for="{{ $brandFieldId }}">Marca</label>
        <select
            id="{{ $brandFieldId }}"
            name="brand_id"
            class="field"
            x-model="brandId"
            :disabled="!clientId || filteredBrands.length === 0"
        >
            <option value="" x-text="brandPlaceholder"></option>
            <template x-for="brand in filteredBrands" :key="brand.id">
                <option :value="brand.id" x-text="brand.name"></option>
            </template>
        </select>
        <p x-show="clientId && filteredBrands.length === 0" class="mt-2 text-xs text-slate-500" style="display:none">
            Este cliente aún no tiene marcas registradas.
        </p>
        <x-input-error :messages="$errors->get('brand_id')" class="mt-2" />
    </div>
</div>
