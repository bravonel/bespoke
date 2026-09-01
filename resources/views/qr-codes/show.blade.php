@php
    $design = array_merge(['foreground' => '#161616', 'background' => '#FFFFFF', 'dots' => 'rounded', 'corners' => 'extra-rounded', 'frame' => 'soft', 'cta' => 'ESCANEA AQUÍ'], $qrCode->design ?: []);
    $dailyMax = max(1, (int) $daily->max('count'));
    $deviceTotal = max(1, (int) $devices->sum());
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('qr-codes.index') }}" class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400 hover:text-[#e91e8c]">← Biblioteca QR</a>
                <div class="mt-3 flex items-center gap-3"><h1 class="page-title truncate">{{ $qrCode->name }}</h1><span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[.16em] {{ $qrCode->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-200 text-slate-500' }}">{{ ['active' => 'Activo', 'paused' => 'Pausado', 'archived' => 'Archivado'][$qrCode->status] }}</span></div>
                <p class="mt-2 truncate text-sm text-slate-500">{{ $qrCode->client?->name ?: 'Sin cliente' }}{{ $qrCode->brand ? ' · '.$qrCode->brand->name : '' }} · creado {{ $qrCode->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('qr-codes.print', $qrCode) }}" target="_blank" class="button-secondary gap-2 whitespace-nowrap"><x-heroicon-o-printer class="h-4 w-4" aria-hidden="true" />Imprimir</a>
                <a href="{{ route('qr-codes.export', $qrCode) }}" class="button-secondary gap-2 whitespace-nowrap" data-tooltip="Exportar datos del QR" aria-label="Exportar datos del QR"><x-heroicon-o-arrow-down-tray class="h-4 w-4" aria-hidden="true" />Datos</a>
                <button type="button" data-open-modal="edit-qr" class="button-primary gap-2 whitespace-nowrap"><x-heroicon-o-pencil class="h-4 w-4" aria-hidden="true" />Editar</button>
            </div>
        </div>
    </x-slot>

    <div class="shell" x-data="qrDesigner(@js(['url' => $qrCode->shortUrl(), 'logo' => $qrCode->logoUrl(), 'design' => $design, 'filename' => \Illuminate\Support\Str::slug($qrCode->name)]))">
        <div class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-6 xl:grid-cols-[25rem_minmax(0,1fr)]">
            <aside class="min-w-0 space-y-5 xl:sticky xl:top-24 xl:self-start">
                <section class="overflow-hidden rounded-[2rem] bg-[#171717] shadow-[0_28px_75px_-30px_rgba(15,23,42,.75)]">
                    <div class="qr-signal-grid flex items-center justify-between px-5 py-4 text-white"><div><p class="text-[10px] font-semibold uppercase tracking-[.22em] text-[#f5a623]">Señal maestra</p><p class="mt-1 text-xs text-white/45">Siempre editable. Siempre medible.</p></div><span class="h-2 w-2 rounded-full {{ $qrCode->status === 'active' ? 'bg-emerald-400' : 'bg-slate-500' }}"></span></div>
                    <div class="bg-stone-100 p-7">
                        <div class="qr-preview-shell" x-bind:data-frame="state.frame"><div x-ref="canvas" class="qr-canvas w-full"></div><div x-show="state.frame !== 'none'" class="mt-2 max-w-full truncate rounded-full bg-[#171717] px-5 py-2 text-center text-[11px] font-bold tracking-[.18em] text-white" x-text="state.cta"></div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-t border-white/10 p-4"><button type="button" x-on:click="download('png')" class="rounded-xl bg-white px-2 py-2.5 text-xs font-semibold text-slate-950 transition hover:bg-stone-100">PNG</button><button type="button" x-on:click="download('svg')" class="rounded-xl border border-white/15 px-2 py-2.5 text-xs font-semibold text-white transition hover:bg-white/5">SVG</button><a href="{{ route('qr-codes.print', $qrCode) }}" target="_blank" class="rounded-xl border border-white/15 px-2 py-2.5 text-center text-xs font-semibold text-white transition hover:bg-white/5">Imprimir</a></div>
                </section>

                <section class="panel p-5">
                    <p class="metric-label">URL dinámica</p>
                    <div class="mt-3 flex items-center gap-2"><code class="min-w-0 flex-1 truncate rounded-xl bg-stone-100 px-3 py-2 text-xs text-slate-600">{{ $qrCode->shortUrl() }}</code><button type="button" x-on:click="copyUrl()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-white text-slate-500 transition hover:bg-stone-50 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2" x-bind:aria-label="copied ? 'URL copiada' : 'Copiar URL'" x-bind:data-tooltip="copied ? 'URL copiada' : 'Copiar URL'"><x-heroicon-o-clipboard-document class="h-4 w-4" aria-hidden="true" /></button></div>
                    <div class="mt-4 border-t border-stone-100 pt-4"><div class="flex items-center justify-between gap-3"><p class="text-xs text-slate-400">Destino actual</p>@if($qrCode->tracking_parameters['enabled'] ?? false)<span class="rounded-full bg-emerald-100 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.12em] text-emerald-700">UTM activo</span>@endif</div><a href="{{ $qrCode->trackedDestinationUrl() }}" target="_blank" rel="noopener" class="mt-1 block truncate text-sm font-medium text-slate-700 hover:text-[#e91e8c]">{{ $qrCode->trackedDestinationUrl() }}</a></div>
                </section>
            </aside>

            <main class="min-w-0 space-y-6">
                <section class="grid gap-4 sm:grid-cols-3">
                    <article class="metric-card"><span class="metric-label">Escaneos</span><strong class="metric-value">{{ number_format($qrCode->scans_count) }}</strong><span class="text-xs text-slate-400">interacciones totales</span></article>
                    <article class="metric-card"><span class="metric-label">Personas aprox.</span><strong class="metric-value">{{ number_format($uniqueScans) }}</strong><span class="text-xs text-slate-400">IPs anonimizadas únicas</span></article>
                    <article class="metric-card"><span class="metric-label">Último pulso</span><strong class="text-xl font-semibold text-slate-950">{{ $qrCode->last_scanned_at?->diffForHumans() ?: 'Sin escaneos' }}</strong><span class="text-xs text-slate-400">actividad más reciente</span></article>
                </section>

                <section class="panel p-6 sm:p-7">
                    <div class="flex items-start justify-between"><div><h2 class="font-semibold text-slate-950">Pulso de los últimos 14 días</h2><p class="mt-1 text-xs text-slate-500">Escaneos diarios registrados por el redirect de Bespoke.</p></div><span class="rounded-full bg-stone-100 px-3 py-1 text-[10px] font-semibold uppercase tracking-[.14em] text-slate-500">En vivo</span></div>
                    <div class="mt-8 grid h-52 grid-cols-[repeat(14,minmax(0,1fr))] items-end gap-1.5 sm:gap-3">
                        @foreach ($daily as $day)
                            <div class="group flex h-full min-w-0 flex-col justify-end">
                                <div class="relative flex flex-1 items-end"><span class="absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 rounded-lg bg-slate-950 px-2 py-1 text-[10px] text-white group-hover:block">{{ $day['count'] }}</span><span class="block w-full rounded-t-lg bg-[#e91e8c] opacity-80 transition group-hover:opacity-100" style="height: {{ $day['count'] ? max(5, round(($day['count'] / $dailyMax) * 100)) : 2 }}%"></span></div>
                                <span class="mt-2 truncate text-center text-[9px] text-slate-400">{{ $loop->even ? $day['label'] : '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="panel p-6">
                        <h2 class="font-semibold text-slate-950">Dispositivos</h2><p class="mt-1 text-xs text-slate-500">Cómo llega la audiencia.</p>
                        <div class="mt-6 space-y-4">
                            @forelse ($devices as $device => $count)
                                <div><div class="mb-2 flex items-center justify-between text-xs"><span class="font-medium capitalize text-slate-700">{{ ['mobile' => 'Móvil', 'desktop' => 'Escritorio', 'tablet' => 'Tablet'][$device] ?? $device }}</span><span class="text-slate-400">{{ $count }} · {{ round(($count / $deviceTotal) * 100) }}%</span></div><div class="h-2 rounded-full bg-stone-100"><span class="block h-full rounded-full bg-[#f5a623]" style="width: {{ ($count / $deviceTotal) * 100 }}%"></span></div></div>
                            @empty <p class="py-8 text-center text-sm text-slate-400">Aún no hay datos de dispositivo.</p> @endforelse
                        </div>
                    </section>
                    <section class="panel p-6">
                        <h2 class="font-semibold text-slate-950">Ubicaciones principales</h2><p class="mt-1 text-xs text-slate-500">Disponibles cuando la infraestructura comparte geolocalización IP.</p>
                        <div class="mt-5 divide-y divide-stone-100">
                            @forelse ($locations as $location => $count)<div class="flex items-center justify-between py-3 text-sm"><span class="truncate text-slate-600">{{ $location }}</span><strong class="ml-3 text-slate-950">{{ $count }}</strong></div>@empty <p class="py-9 text-center text-sm text-slate-400">Sin ubicación disponible todavía.</p> @endforelse
                        </div>
                    </section>
                </div>

                <section class="panel overflow-hidden">
                    <div class="border-b border-stone-100 px-6 py-5"><h2 class="font-semibold text-slate-950">Actividad reciente</h2><p class="mt-1 text-xs text-slate-500">Los últimos 20 escaneos, sin almacenar la IP en texto plano.</p></div>
                    <div class="overflow-x-auto"><table class="table"><thead><tr><th>Fecha</th><th>Dispositivo</th><th>Navegador</th><th>Ubicación</th><th>Referente</th></tr></thead><tbody class="divide-y divide-stone-100">@forelse ($recentScans as $scan)<tr><td class="whitespace-nowrap">{{ $scan->created_at->translatedFormat('d M Y, H:i') }}</td><td class="capitalize">{{ $scan->device }}</td><td>{{ $scan->browser }}</td><td>{{ collect([$scan->city, $scan->country])->filter()->join(', ') ?: 'No disponible' }}</td><td class="max-w-48 truncate">{{ $scan->referrer ?: 'Directo / cámara' }}</td></tr>@empty<tr><td colspan="5" class="py-12 text-center text-slate-400">Escanea el código para registrar la primera interacción.</td></tr>@endforelse</tbody></table></div>
                </section>
            </main>
        </div>

        <x-modal name="edit-qr" focusable>
            <form method="POST" action="{{ route('qr-codes.update', $qrCode) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <div class="modal-header flex items-start justify-between"><div><h2 class="text-lg font-semibold text-slate-950">Editar señal</h2><p class="mt-1 text-sm text-slate-500">El QR impreso seguirá siendo el mismo.</p></div><x-icon-button label="Cerrar edición" icon="x-mark" size="sm" x-on:click="$dispatch('close')" /></div>
                <div class="modal-body space-y-5">
                    <div><label class="field-label" for="edit-name">Nombre</label><input id="edit-name" name="name" class="field" value="{{ $qrCode->name }}" required></div>
                    <div><label class="field-label" for="edit-url">Destino</label><input id="edit-url" name="destination_url" type="url" class="field" value="{{ $qrCode->destination_url }}" required x-on:input="$dispatch('destination-url-changed', $event.target.value)"></div>
                    @include('qr-codes._utm-fields', [
                        'compact' => true,
                        'baseUrl' => $qrCode->destination_url,
                        'tracking' => $qrCode->tracking_parameters ?: [],
                    ])
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label" for="edit-client">Cliente</label><select id="edit-client" name="client_id" class="field"><option value="">Sin asignar</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected($qrCode->client_id === $client->id)>{{ $client->name }}</option>@endforeach</select></div><div><label class="field-label" for="edit-brand">Marca</label><select id="edit-brand" name="brand_id" class="field"><option value="">Sin asignar</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected($qrCode->brand_id === $brand->id)>{{ $brand->name }}</option>@endforeach</select></div></div>
                    <div><label class="field-label" for="edit-status">Estatus</label><select id="edit-status" name="status" class="field"><option value="active" @selected($qrCode->status === 'active')>Activo</option><option value="paused" @selected($qrCode->status === 'paused')>Pausado</option><option value="archived" @selected($qrCode->status === 'archived')>Archivado</option></select></div>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="rounded-2xl border border-stone-200 p-3 text-xs text-slate-500">Color del código<div class="mt-2 flex items-center gap-2"><input type="color" name="foreground" x-model="state.foreground" class="h-9 w-9"><span x-text="state.foreground"></span></div></label><label class="rounded-2xl border border-stone-200 p-3 text-xs text-slate-500">Color de fondo<div class="mt-2 flex items-center gap-2"><input type="color" name="background" x-model="state.background" class="h-9 w-9"><span x-text="state.background"></span></div></label></div>
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label" for="edit-dots">Patrón</label><select id="edit-dots" name="dots" x-model="state.dots" class="field"><option value="rounded">Redondeado</option><option value="dots">Puntos</option><option value="square">Clásico</option><option value="classy-rounded">Editorial</option></select></div><div><label class="field-label" for="edit-frame">Marco</label><select id="edit-frame" name="frame" x-model="state.frame" class="field"><option value="soft">Suave</option><option value="ticket">Ticket</option><option value="none">Sin marco</option></select></div></div>
                    <input type="hidden" name="corners" x-bind:value="state.corners"><div><label class="field-label" for="edit-cta">Llamado a la acción</label><input id="edit-cta" name="cta" x-model="state.cta" class="field" maxlength="28"></div>
                    <div><label class="field-label" for="edit-logo">Reemplazar logo</label><input x-ref="logoInput" id="edit-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="field" x-on:change="loadLogo($event)">@if($qrCode->logo_path)<label class="mt-3 flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="remove_logo" value="1" x-on:change="if ($event.target.checked) removeLogo()"> Quitar logo actual</label>@endif</div>
                </div>
                <div class="modal-footer"><button type="button" x-on:click="$dispatch('close')" class="button-secondary">Cancelar</button><button class="button-primary">Guardar cambios</button></div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
