<div
    x-data="{
        open: false,
        label: '',
        left: 0,
        top: 0,
        placement: 'top',
        align: 'center',
        show(detail) {
            this.label = detail.label;
            this.left = detail.left;
            this.top = detail.top;
            this.placement = detail.placement;
            this.align = detail.align || 'center';
            this.open = true;
        },
    }"
    x-on:app-tooltip-show.window="show($event.detail)"
    x-on:app-tooltip-hide.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="translate-y-1 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="pointer-events-none fixed z-[90] max-w-[min(16rem,calc(100vw-1.5rem))] rounded-xl bg-slate-950 px-3 py-1.5 text-center text-xs font-medium text-white shadow-xl"
    :style="`left: ${left}px; top: ${top}px; transform: translate(${align === 'right' ? '-100%' : align === 'left' ? '0' : '-50%'}, ${placement === 'top' ? '-100%' : '0'});`"
    role="tooltip"
    style="display:none"
    x-text="label"
></div>
