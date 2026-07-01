@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 text-slate-950 transition duration-150 ease-in-out focus:outline-none'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-slate-500 transition duration-150 ease-in-out hover:border-stone-300 hover:text-slate-700 focus:outline-none focus:border-stone-300 focus:text-slate-700';
@endphp

<a
    {{ $attributes->merge(['class' => $classes]) }}
    @if($active ?? false) style="border-color:#F5A623" @endif
>
    {{ $slot }}
</a>
