@props([
    'label',
    'icon',
    'tone' => 'neutral',
    'size' => 'md',
])

@php
    $toneClasses = [
        'neutral' => 'border-stone-200 bg-white text-slate-500 hover:border-stone-300 hover:bg-stone-50 hover:text-slate-800 focus-visible:ring-slate-300',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-600 hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 focus-visible:ring-rose-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100 hover:text-amber-800 focus-visible:ring-amber-300',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-600 hover:border-emerald-300 hover:bg-emerald-100 hover:text-emerald-700 focus-visible:ring-emerald-300',
    ][$tone] ?? '';

    $sizeClasses = $size === 'sm' ? 'h-9 w-9 rounded-xl' : 'h-10 w-10 rounded-2xl';
    $iconClasses = $size === 'sm' ? 'h-4 w-4' : 'h-[1.125rem] w-[1.125rem]';
@endphp

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => "inline-flex shrink-0 items-center justify-center border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {$sizeClasses} {$toneClasses}",
    ]) }}
    aria-label="{{ $label }}"
    data-tooltip="{{ $label }}"
>
    <x-dynamic-component :component="'heroicon-o-'.$icon" class="{{ $iconClasses }}" aria-hidden="true" />
</button>
