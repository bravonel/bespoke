<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('qr-codes.index') }}" class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400 hover:text-[#e91e8c]">← QR Studio</a>
                <h1 class="page-title mt-3">Diseña una nueva señal</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Configura el destino, conéctalo con un cliente y haz que el código se reconozca antes de escanearse.</p>
            </div>
            <div class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">QR dinámico · tracking incluido</div>
        </div>
    </x-slot>

    <div class="shell" x-data="qrDesigner(@js(['design' => $defaults]))">
        <form method="POST" action="{{ route('qr-codes.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(24rem,.85fr)]">
            @csrf
            <div class="space-y-5">
                <section class="panel p-6 sm:p-8">
                    <div class="flex items-center gap-4 border-b border-stone-100 pb-5"><span class="grid h-9 w-9 place-items-center rounded-xl bg-[#171717] text-xs font-bold text-white">01</span><div><h2 class="font-semibold text-slate-950">Destino y propiedad</h2><p class="text-xs text-slate-500">La información operativa de la campaña.</p></div></div>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label for="name" class="field-label">Nombre de la campaña</label><input id="name" name="name" value="{{ old('name') }}" class="field" placeholder="Ej. Congreso Cardiología 2026" required><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                        <div class="sm:col-span-2"><label for="destination_url" class="field-label">Vínculo de destino</label><input id="destination_url" name="destination_url" type="url" value="{{ old('destination_url') }}" class="field" placeholder="https://cliente.com/campaña" required x-on:input="$dispatch('destination-url-changed', $event.target.value)"><p class="mt-2 text-xs text-slate-400">Podrás cambiarlo después sin modificar ni reimprimir el QR.</p><x-input-error :messages="$errors->get('destination_url')" class="mt-2" /></div>
                        <div><label for="client_id" class="field-label">Cliente</label><select id="client_id" name="client_id" class="field"><option value="">Sin asignar</option>@foreach ($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
                        <div><label for="brand_id" class="field-label">Marca</label><select id="brand_id" name="brand_id" class="field"><option value="">Sin asignar</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}" data-client="{{ $brand->client_id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
                        <input type="hidden" name="status" value="active">
                    </div>
                </section>

                @include('qr-codes._utm-fields', [
                    'baseUrl' => old('destination_url', ''),
                    'tracking' => [
                        'enabled' => old('utm_enabled', false),
                        'utm_source' => old('utm_source', ''),
                        'utm_medium' => old('utm_medium', ''),
                        'utm_campaign' => old('utm_campaign', ''),
                        'utm_term' => old('utm_term', ''),
                        'utm_content' => old('utm_content', ''),
                        'custom' => old('custom_parameters', []),
                    ],
                ])

                <section class="panel p-6 sm:p-8">
                    <div class="flex items-center gap-4 border-b border-stone-100 pb-5"><span class="grid h-9 w-9 place-items-center rounded-xl bg-[#e91e8c] text-xs font-bold text-white">03</span><div><h2 class="font-semibold text-slate-950">Identidad visual</h2><p class="text-xs text-slate-500">Dale una voz propia al patrón.</p></div></div>
                    <div class="mt-6 grid gap-6 lg:grid-cols-2">
                        <div>
                            <label class="field-label">Paleta</label>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <label class="rounded-2xl border border-stone-200 p-3"><span class="text-xs text-slate-500">Código</span><div class="mt-2 flex items-center gap-2"><input type="color" name="foreground" x-model="state.foreground" class="h-9 w-9 cursor-pointer rounded-lg border-0 bg-transparent p-0"><span class="text-xs font-medium" x-text="state.foreground"></span></div></label>
                                <label class="rounded-2xl border border-stone-200 p-3"><span class="text-xs text-slate-500">Fondo</span><div class="mt-2 flex items-center gap-2"><input type="color" name="background" x-model="state.background" class="h-9 w-9 cursor-pointer rounded-lg border-0 bg-transparent p-0"><span class="text-xs font-medium" x-text="state.background"></span></div></label>
                            </div>
                        </div>
                        <div>
                            <label for="logo" class="field-label">Logo del cliente</label>
                            <label class="mt-3 flex cursor-pointer items-center gap-3 rounded-2xl border border-dashed border-stone-300 p-4 transition hover:border-[#e91e8c] hover:bg-pink-50/40"><span class="grid h-9 w-9 place-items-center rounded-xl bg-stone-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4m0 0L7 9m5-5 5 5M4 15v4h16v-4"/></svg></span><span><strong class="block text-sm font-medium text-slate-700">Subir logo</strong><small class="text-xs text-slate-400">PNG, JPG o WebP · máx. 2 MB</small></span><input x-ref="logoInput" id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" x-on:change="loadLogo($event)"></label>
                        </div>
                        <div>
                            <span class="field-label">Forma del patrón</span>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2"><label class="qr-choice"><span>Redondeado</span><input type="radio" name="dots" value="rounded" x-model="state.dots"></label><label class="qr-choice"><span>Puntos</span><input type="radio" name="dots" value="dots" x-model="state.dots"></label><label class="qr-choice"><span>Clásico</span><input type="radio" name="dots" value="square" x-model="state.dots"></label><label class="qr-choice"><span>Editorial</span><input type="radio" name="dots" value="classy-rounded" x-model="state.dots"></label></div>
                        </div>
                        <div>
                            <span class="field-label">Marco</span>
                            <div class="mt-3 grid gap-2"><label class="qr-choice"><span>Suave</span><input type="radio" name="frame" value="soft" x-model="state.frame"></label><label class="qr-choice"><span>Ticket</span><input type="radio" name="frame" value="ticket" x-model="state.frame"></label><label class="qr-choice"><span>Sin marco</span><input type="radio" name="frame" value="none" x-model="state.frame"></label></div>
                        </div>
                        <div><label for="corners" class="field-label">Esquinas de lectura</label><select id="corners" name="corners" class="field" x-model="state.corners"><option value="extra-rounded">Redondeadas</option><option value="square">Clásicas</option><option value="dot">Circulares</option></select></div>
                        <div><label for="cta" class="field-label">Llamado a la acción</label><input id="cta" name="cta" class="field" maxlength="28" x-model="state.cta" placeholder="ESCANEA AQUÍ"></div>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24 xl:self-start">
                <div class="overflow-hidden rounded-[2rem] bg-[#171717] shadow-[0_28px_75px_-30px_rgba(15,23,42,.75)]">
                    <div class="qr-signal-grid px-5 py-5 text-white"><div class="flex items-center justify-between"><div><p class="text-[10px] font-semibold uppercase tracking-[.22em] text-[#f5a623]">Vista previa en vivo</p><p class="mt-1 text-xs text-white/50">Alta corrección de error para soportar logos.</p></div><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span></div></div>
                    <div class="bg-stone-100 px-5 py-9 sm:px-8">
                        <div class="qr-preview-shell" x-bind:data-frame="state.frame">
                            <div x-ref="canvas" class="qr-canvas w-full"></div>
                            <div x-show="state.frame !== 'none'" class="mt-2 max-w-full truncate rounded-full bg-[#171717] px-5 py-2 text-center text-[11px] font-bold tracking-[.18em] text-white" x-text="state.cta || 'ESCANEA AQUÍ'"></div>
                        </div>
                        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-4"><div class="flex gap-3"><svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg><p class="text-xs leading-5 text-slate-500"><strong class="text-slate-700">Listo para evolucionar.</strong> El QR siempre apuntará a Bespoke; el destino real podrá editarse en cualquier momento.</p></div></div>
                    </div>
                    <div class="flex gap-3 border-t border-white/10 p-5"><a href="{{ route('qr-codes.index') }}" class="flex-1 rounded-2xl border border-white/15 px-4 py-3 text-center text-sm font-medium text-white/70 hover:bg-white/5">Cancelar</a><button class="flex-[1.6] rounded-2xl bg-[#e91e8c] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#d4167c]">Crear QR dinámico</button></div>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>
