@php
    $utmCompact = $compact ?? false;
    $utmTracking = array_merge([
        'enabled' => false,
        'utm_source' => '',
        'utm_medium' => '',
        'utm_campaign' => '',
        'utm_term' => '',
        'utm_content' => '',
        'custom' => [],
    ], $tracking ?? []);
@endphp

<section
    x-data="utmBuilder(@js([
        ...$utmTracking,
        'baseUrl' => $baseUrl ?? '',
    ]))"
    x-on:destination-url-changed.window="baseUrl = $event.detail"
    class="{{ $utmCompact ? 'rounded-2xl border border-stone-200 bg-stone-50/80 p-4' : 'panel p-6 sm:p-8' }}"
>
    <button type="button" class="flex w-full items-center justify-between gap-4 text-left" x-on:click="open = !open" x-bind:aria-expanded="open">
        <span class="flex min-w-0 items-center gap-4">
            @unless ($utmCompact)
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#f5a623] text-xs font-bold text-white">02</span>
            @endunless
            <span>
                <strong class="block {{ $utmCompact ? 'text-sm' : '' }} font-semibold text-slate-950">Parámetros UTM</strong>
                <span class="mt-1 block text-xs text-slate-500">Conecta el QR con Google Analytics y otras herramientas.</span>
            </span>
        </span>
        <span class="flex shrink-0 items-center gap-3">
            <span x-show="enabled" class="hidden rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[.14em] text-emerald-700 sm:inline-flex">Activo</span>
            <svg class="h-4 w-4 text-slate-400 transition" x-bind:class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </span>
    </button>

    <div x-show="open" class="mt-5 border-t border-stone-200 pt-5">
        <div class="flex items-start justify-between gap-5 rounded-2xl border border-stone-200 bg-white p-4">
            <div>
                <p class="text-sm font-semibold text-slate-800">Añadir UTMs al destino</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Los parámetros se agregan al momento del escaneo y pueden editarse después.</p>
            </div>
            <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center">
                <input type="hidden" name="utm_enabled" value="0">
                <input type="checkbox" name="utm_enabled" value="1" class="peer sr-only" x-model="enabled">
                <span class="h-7 w-12 rounded-full bg-stone-300 transition peer-checked:bg-emerald-600 peer-focus:ring-4 peer-focus:ring-emerald-100 after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        <div x-show="enabled" x-transition.opacity class="mt-5 space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="utm-source-{{ $utmCompact ? 'edit' : 'create' }}">Source</label>
                    <input id="utm-source-{{ $utmCompact ? 'edit' : 'create' }}" name="utm_source" class="field" x-model="source" list="utm-source-options" placeholder="newsletter, linkedin, evento">
                </div>
                <div>
                    <label class="field-label" for="utm-medium-{{ $utmCompact ? 'edit' : 'create' }}">Medium</label>
                    <input id="utm-medium-{{ $utmCompact ? 'edit' : 'create' }}" name="utm_medium" class="field" x-model="medium" list="utm-medium-options" placeholder="qr, print, poster">
                </div>
                <div>
                    <label class="field-label" for="utm-campaign-{{ $utmCompact ? 'edit' : 'create' }}">Campaign</label>
                    <input id="utm-campaign-{{ $utmCompact ? 'edit' : 'create' }}" name="utm_campaign" class="field" x-model="campaign" placeholder="congreso-cardiologia-2026">
                </div>
                <div>
                    <label class="field-label" for="utm-term-{{ $utmCompact ? 'edit' : 'create' }}">Term <span class="font-normal text-slate-400">(opcional)</span></label>
                    <input id="utm-term-{{ $utmCompact ? 'edit' : 'create' }}" name="utm_term" class="field" x-model="term" placeholder="audiencia o palabra clave">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="utm-content-{{ $utmCompact ? 'edit' : 'create' }}">Content <span class="font-normal text-slate-400">(opcional)</span></label>
                    <input id="utm-content-{{ $utmCompact ? 'edit' : 'create' }}" name="utm_content" class="field" x-model="content" placeholder="stand-a, folleto-frontal, variante-azul">
                </div>
            </div>

            <datalist id="utm-source-options"><option value="newsletter"><option value="linkedin"><option value="instagram"><option value="evento"><option value="material-impreso"></datalist>
            <datalist id="utm-medium-options"><option value="qr"><option value="print"><option value="poster"><option value="packaging"><option value="email"></datalist>

            <div x-ref="customList" class="space-y-3">
                <template x-for="(parameter, index) in custom" x-bind:key="index">
                    <div class="grid grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)_auto] items-end gap-2">
                        <div class="min-w-0"><label class="field-label">Parámetro</label><input class="field" x-model="parameter.key" x-bind:name="`custom_parameters[${index}][key]`" placeholder="ref"></div>
                        <div class="min-w-0"><label class="field-label">Valor</label><input class="field" x-model="parameter.value" x-bind:name="`custom_parameters[${index}][value]`" placeholder="stand-a"></div>
                        <button type="button" class="mb-0.5 grid h-11 w-11 place-items-center rounded-xl border border-stone-200 bg-white text-slate-400 transition hover:border-rose-200 hover:text-rose-600" x-on:click="removeCustom(index)" aria-label="Quitar parámetro">×</button>
                    </div>
                </template>
                <button type="button" class="inline-flex items-center gap-2 text-xs font-semibold text-[#e91e8c] hover:text-[#c91576]" x-on:click="addCustom()" x-bind:disabled="custom.length >= 10">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-pink-100">+</span> Añadir parámetro personalizado
                </button>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Vista previa de URL</p>
                <code class="mt-2 block break-all rounded-2xl border border-stone-200 bg-white px-4 py-3 text-xs leading-5 text-slate-600" x-text="previewUrl()"></code>
            </div>
        </div>
    </div>
</section>
