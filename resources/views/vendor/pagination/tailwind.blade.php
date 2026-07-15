@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white/90 px-4 py-3 text-sm shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="text-slate-500">
            Mostrando
            <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span>
            a
            <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span>
            de
            <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-10 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-slate-300">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-stone-200 bg-white px-3 font-medium text-slate-700 transition hover:border-stone-300 hover:bg-stone-50">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl px-3 font-semibold text-white shadow-sm" style="background-color:var(--brand-magenta)">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border border-stone-200 bg-white px-3 font-medium text-slate-700 transition hover:border-stone-300 hover:bg-stone-50">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-stone-200 bg-white px-3 font-medium text-slate-700 transition hover:border-stone-300 hover:bg-stone-50">Siguiente</a>
            @else
                <span class="inline-flex min-h-10 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-slate-300">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
