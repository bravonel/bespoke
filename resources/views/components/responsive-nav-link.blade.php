@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 ps-3 pe-4 py-2 text-start text-base font-semibold text-slate-900 transition duration-150 ease-in-out focus:outline-none'
            : 'block w-full border-l-4 border-transparent ps-3 pe-4 py-2 text-start text-base font-medium text-slate-600 transition duration-150 ease-in-out hover:border-stone-300 hover:bg-stone-50 hover:text-slate-800 focus:border-stone-300 focus:bg-stone-50 focus:text-slate-800 focus:outline-none';
@endphp

<a
    {{ $attributes->merge(['class' => $classes]) }}
    @if($active ?? false) style="border-color:#F5A623; background-color:rgba(245,166,35,0.06)" @endif
>
    {{ $slot }}
</a>
