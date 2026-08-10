@props(['value'])

@php
    use App\Support\OperationalLabels;

    $palette = match ($value) {
        'active', 'finalized' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'done' => 'bg-sky-100 text-sky-700 ring-sky-200',
        'in_review', 'in_progress' => 'bg-amber-100 text-amber-700 ring-amber-200',
        'blocked', 'critical' => 'bg-rose-100 text-rose-700 ring-rose-200',
        'on_hold', 'paused' => 'bg-slate-200 text-slate-700 ring-slate-300',
        default => 'bg-stone-100 text-stone-700 ring-stone-200',
    };

    $label = OperationalLabels::get($value);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$palette}"]) }}>
    {{ $label }}
</span>
