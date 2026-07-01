@props(['value'])

@php
    $palette = match ($value) {
        'active', 'done' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'in_review', 'in_progress' => 'bg-amber-100 text-amber-700 ring-amber-200',
        'blocked', 'critical' => 'bg-rose-100 text-rose-700 ring-rose-200',
        'on_hold', 'paused' => 'bg-slate-200 text-slate-700 ring-slate-300',
        default => 'bg-stone-100 text-stone-700 ring-stone-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset {$palette}"]) }}>
    {{ str_replace('_', ' ', $value) }}
</span>
