<x-app-layout>
    <x-slot name="header">
        <div class="overflow-hidden rounded-[2rem] bg-[#171717] text-white shadow-[0_28px_70px_-35px_rgba(15,23,42,.75)]">
            <div class="qr-signal-grid relative px-6 py-8 sm:px-9 lg:flex lg:items-end lg:justify-between lg:px-11 lg:py-10">
                <div class="pointer-events-none absolute -right-8 -top-16 h-52 w-52 rounded-full bg-[#e91e8c] opacity-25 blur-3xl"></div>
                <div class="pointer-events-none absolute right-48 top-10 h-28 w-28 rounded-full bg-[#f5a623] opacity-20 blur-3xl"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-[#171717]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 20h3"/></svg>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-[.3em] text-[#f5a623]">Bespoke Signal Lab</p>
                    </div>
                    <h1 class="mt-5 max-w-2xl text-3xl font-semibold tracking-[-.04em] sm:text-4xl">Cada escaneo cuenta una historia.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/60">Crea códigos dinámicos con identidad de marca, cambia su destino cuando lo necesites y mide el viaje completo desde el mundo físico.</p>
                </div>
                <a href="{{ route('qr-codes.create') }}" class="relative mt-6 inline-flex items-center gap-2 rounded-2xl bg-[#e91e8c] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#d4167c] lg:mt-0">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Crear nuevo QR
                </a>
            </div>
        </div>
    </x-slot>

    <div class="shell space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="metric-card">
                <span class="metric-label">QR creados</span>
                <div class="flex items-end justify-between"><strong class="metric-value">{{ number_format($summary['total']) }}</strong><span class="rounded-full bg-stone-100 px-3 py-1 text-xs text-slate-500">{{ $summary['active'] }} activos</span></div>
            </article>
            <article class="metric-card">
                <span class="metric-label">Escaneos totales</span>
                <strong class="metric-value">{{ number_format($summary['scans']) }}</strong>
            </article>
            <article class="metric-card">
                <span class="metric-label">Últimos 30 días</span>
                <div class="flex items-end justify-between"><strong class="metric-value">{{ number_format($summary['recent_scans']) }}</strong><span class="text-xs font-semibold {{ $summary['trend'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $summary['trend'] >= 0 ? '+' : '' }}{{ $summary['trend'] }}%</span></div>
            </article>
            <article class="metric-card bg-[#fff8ec]/90">
                <span class="metric-label">Sistema</span>
                <div><strong class="block text-lg font-semibold text-slate-950">Tracking activo</strong><span class="mt-1 block text-xs text-slate-500">URLs editables sin reimprimir</span></div>
            </article>
        </section>

        <section class="panel p-5">
            <form method="GET" class="grid gap-3 md:grid-cols-[1fr_15rem_12rem_auto] md:items-end">
                <div>
                    <label for="qr-search" class="field-label">Buscar campaña</label>
                    <input id="qr-search" name="q" value="{{ $filters['q'] }}" class="field" placeholder="Nombre, cliente o destino…">
                </div>
                <div>
                    <label for="qr-client" class="field-label">Cliente</label>
                    <select id="qr-client" name="client_id" class="field">
                        <option value="">Todos los clientes</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) $filters['client_id'] === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="qr-status" class="field-label">Estatus</label>
                    <select id="qr-status" name="status" class="field">
                        <option value="">Todos</option>
                        <option value="active" @selected($filters['status'] === 'active')>Activo</option>
                        <option value="paused" @selected($filters['status'] === 'paused')>Pausado</option>
                        <option value="archived" @selected($filters['status'] === 'archived')>Archivado</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="button-secondary">Filtrar</button>
                    @if (collect($filters)->filter()->isNotEmpty())<a href="{{ route('qr-codes.index') }}" class="button-secondary">Limpiar</a>@endif
                </div>
            </form>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <div><h2 class="text-lg font-semibold text-slate-950">Biblioteca de señales</h2><p class="mt-1 text-sm text-slate-500">Tus códigos, su destino actual y el pulso de cada campaña.</p></div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($qrCodes as $qrCode)
                    @php($design = $qrCode->design ?: [])
                    <a href="{{ route('qr-codes.show', $qrCode) }}" class="group panel flex min-h-52 gap-5 p-5 transition hover:-translate-y-1 hover:border-stone-200 hover:shadow-[0_28px_70px_-30px_rgba(15,23,42,.45)]">
                        <div class="shrink-0 self-start rounded-2xl border border-stone-200 bg-white p-2 shadow-sm" x-data="qrMini(@js(['url' => $qrCode->shortUrl(), 'logo' => $qrCode->logoUrl(), 'design' => $design]))">
                            <div x-ref="canvas" class="qr-canvas h-[104px] w-[104px]"></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[.16em] {{ $qrCode->status === 'active' ? 'text-emerald-600' : 'text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $qrCode->status === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    {{ ['active' => 'Activo', 'paused' => 'Pausado', 'archived' => 'Archivado'][$qrCode->status] }}
                                </span>
                                <svg class="h-4 w-4 text-slate-300 transition group-hover:translate-x-1 group-hover:text-[#e91e8c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </div>
                            <h3 class="mt-3 truncate text-base font-semibold text-slate-950">{{ $qrCode->name }}</h3>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $qrCode->client?->name ?: 'Sin cliente' }}{{ $qrCode->brand ? ' · '.$qrCode->brand->name : '' }}</p>
                            <div class="mt-5 flex items-end justify-between border-t border-stone-100 pt-4">
                                <div><strong class="block text-2xl font-semibold tracking-tight text-slate-950">{{ number_format($qrCode->scans_count) }}</strong><span class="text-[11px] uppercase tracking-[.14em] text-slate-400">escaneos</span></div>
                                <span class="max-w-24 truncate rounded-full bg-stone-100 px-2.5 py-1 text-[10px] text-slate-500">/q/{{ $qrCode->slug }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="panel col-span-full px-6 py-16 text-center">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-stone-100 text-slate-500"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM18 18h3v3h-3z"/></svg></div>
                        <h3 class="mt-4 font-semibold text-slate-950">Todavía no hay señales en el aire</h3>
                        <p class="mt-2 text-sm text-slate-500">Crea el primer QR dinámico de Bespoke OS.</p>
                        <a href="{{ route('qr-codes.create') }}" class="button-primary mt-5">Crear primer QR</a>
                    </div>
                @endforelse
            </div>
            <div class="mt-6">{{ $qrCodes->links() }}</div>
        </section>
    </div>
</x-app-layout>
