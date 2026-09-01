<div
    x-data="{
        open: false,
        mode: 'confirm',
        tone: 'warning',
        title: '',
        message: '',
        detail: '',
        label: '',
        placeholder: '',
        confirmLabel: 'Confirmar',
        cancelLabel: 'Cancelar',
        value: '',
        validationMessage: '',
        required: false,
        resolver: null,
        previousFocus: null,
        bodyWasLocked: false,
        focusables() {
            return [...this.$el.querySelectorAll('button, textarea, input, select, a[href], [tabindex]:not([tabindex=\'-1\'])')]
                .filter((element) => !element.disabled && element.offsetParent !== null);
        },
        cycleFocus(event) {
            const elements = this.focusables();

            if (!elements.length) return;

            const currentIndex = elements.indexOf(document.activeElement);
            const nextIndex = event.shiftKey
                ? (currentIndex <= 0 ? elements.length - 1 : currentIndex - 1)
                : (currentIndex === elements.length - 1 ? 0 : currentIndex + 1);

            elements[nextIndex]?.focus();
        },
        show(options) {
            this.mode = options.mode || 'confirm';
            this.tone = options.tone || (this.mode === 'error' ? 'danger' : 'warning');
            this.title = options.title || 'Confirma esta acción';
            this.message = options.message || '';
            this.detail = options.detail || '';
            this.label = options.label || 'Motivo';
            this.placeholder = options.placeholder || '';
            this.confirmLabel = options.confirmLabel || (this.mode === 'error' ? 'Entendido' : 'Confirmar');
            this.cancelLabel = options.cancelLabel || 'Cancelar';
            this.value = options.value || '';
            this.required = Boolean(options.required);
            this.validationMessage = '';
            this.resolver = options.resolve || null;
            this.previousFocus = document.activeElement;
            this.bodyWasLocked = document.body.classList.contains('overflow-hidden');
            document.body.classList.add('overflow-hidden');
            window.bespokeDialogIsOpen = true;
            this.open = true;

            this.$nextTick(() => {
                (this.mode === 'prompt' ? this.$refs.input : this.$refs.confirm)?.focus();
            });
        },
        complete(result) {
            const resolve = this.resolver;
            const previousFocus = this.previousFocus;

            this.open = false;
            this.resolver = null;

            if (!this.bodyWasLocked) {
                document.body.classList.remove('overflow-hidden');
            }

            window.setTimeout(() => {
                window.bespokeDialogIsOpen = false;
                previousFocus?.focus?.();
            }, 0);
            resolve?.(result);
        },
        confirm() {
            if (this.mode === 'prompt') {
                const cleanValue = this.value.trim();

                if (this.required && !cleanValue) {
                    this.validationMessage = 'Escribe un motivo antes de continuar.';
                    this.$refs.input?.focus();
                    return;
                }

                this.complete(cleanValue);
                return;
            }

            this.complete(true);
        },
        cancel() {
            if (this.mode === 'error') {
                this.complete(true);
                return;
            }

            this.complete(this.mode === 'prompt' ? null : false);
        },
        kicker() {
            if (this.mode === 'prompt') return 'Motivo requerido';
            if (this.mode === 'error') return 'No pudimos completar la acción';
            return 'Confirmación';
        },
    }"
    x-on:app-dialog-open.window="show($event.detail)"
    x-on:keydown.escape.window="if (open) cancel()"
    x-on:keydown.tab.prevent="if (open) cycleFocus($event)"
    x-show="open"
    class="fixed inset-0 z-[70] flex items-center justify-center px-4 py-6 sm:px-6"
    style="display:none"
    role="dialog"
    aria-modal="true"
    aria-labelledby="app-dialog-title"
    aria-describedby="app-dialog-description"
>
    <div
        x-show="open"
        x-on:click="cancel()"
        class="fixed inset-0 bg-slate-950/50 backdrop-blur-[5px]"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <section
        x-show="open"
        x-on:click.stop
        class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-white/70 bg-white shadow-[0_32px_90px_-24px_rgba(15,23,42,0.55)]"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="translate-y-3 scale-95 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="translate-y-3 scale-95 opacity-0"
    >
        <div class="h-1.5 w-full" :class="{
            'bg-rose-500': tone === 'danger',
            'bg-amber-400': tone === 'warning',
            'bg-emerald-500': tone === 'success',
            'bg-sky-500': tone === 'info',
        }"></div>

        <div class="p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border"
                    :class="{
                        'border-rose-200 bg-rose-50 text-rose-700': tone === 'danger',
                        'border-amber-200 bg-amber-50 text-amber-800': tone === 'warning',
                        'border-emerald-200 bg-emerald-50 text-emerald-700': tone === 'success',
                        'border-sky-200 bg-sky-50 text-sky-700': tone === 'info',
                    }"
                    aria-hidden="true"
                >
                    <svg x-show="tone === 'danger'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.38c.865-1.5 3.03-1.5 3.896 0l7.355 12.746ZM12 15.75h.008v.008H12v-.008Z" />
                    </svg>
                    <svg x-show="tone === 'warning'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A9.75 9.75 0 1 0 21.75 12 9.75 9.75 0 0 0 12 2.714ZM12 15.75h.008v.008H12v-.008Z" />
                    </svg>
                    <svg x-show="tone === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <svg x-show="tone === 'info'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25 12 10.5m0 0 .75.75M12 10.5v6.75m9.75-5.25A9.75 9.75 0 1 1 2.25 12a9.75 9.75 0 0 1 19.5 0ZM12 7.875h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="page-kicker" x-text="kicker()"></p>
                    <h2 id="app-dialog-title" class="mt-2 text-xl font-semibold text-slate-950" x-text="title"></h2>
                    <p id="app-dialog-description" class="mt-2 text-sm leading-6 text-slate-600" x-text="message"></p>
                    <p x-show="detail" class="mt-2 text-xs leading-5 text-slate-500" x-text="detail"></p>
                </div>
            </div>

            <div x-show="mode === 'prompt'" class="mt-6">
                <label for="app-dialog-input" class="field-label" x-text="label"></label>
                <textarea
                    id="app-dialog-input"
                    x-ref="input"
                    x-model="value"
                    x-bind:placeholder="placeholder"
                    x-on:input="validationMessage = ''"
                    x-on:keydown.meta.enter.prevent="confirm()"
                    x-on:keydown.ctrl.enter.prevent="confirm()"
                    rows="4"
                    maxlength="1000"
                    class="field resize-none"
                ></textarea>
                <div class="mt-2 flex items-start justify-between gap-4 text-xs">
                    <p x-show="validationMessage" class="font-medium text-rose-700" x-text="validationMessage"></p>
                    <p class="ml-auto shrink-0 text-slate-400"><span x-text="value.length"></span>/1000</p>
                </div>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    x-show="mode !== 'error'"
                    type="button"
                    x-on:click="cancel()"
                    class="button-secondary justify-center"
                    x-text="cancelLabel"
                ></button>
                <button
                    x-ref="confirm"
                    type="button"
                    x-on:click="confirm()"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="{
                        'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500': tone === 'danger',
                        'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500': tone === 'warning',
                        'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500': tone === 'success',
                        'bg-sky-600 hover:bg-sky-700 focus:ring-sky-500': tone === 'info',
                    }"
                    x-text="confirmLabel"
                ></button>
            </div>
        </div>
    </section>
</div>
