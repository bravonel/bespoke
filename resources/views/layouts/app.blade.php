<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bespoke OS') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon-180.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-['Space_Grotesk'] antialiased">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="pt-10 pb-8">
                    <div class="shell">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pb-16 {{ isset($header) ? '' : 'pt-10' }}">
                @if (session('status'))
                    <div class="shell mb-6">
                        <div class="panel border border-emerald-200 bg-emerald-50/90 px-5 py-4 text-sm font-medium text-emerald-800">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- Task Drawer (global) -->
            <div
                x-data="taskDrawer()"
                x-on:open-task-drawer.window="open($event.detail.url)"
                x-on:keydown.escape.window="close()"
                x-show="isOpen"
                class="fixed inset-0 z-50"
                style="display:none"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0"
                    style="background: rgba(15,23,42,0.45); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
                    x-on:click="close()"
                    x-show="isOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                ></div>

                <!-- Drawer panel -->
                <div
                    class="task-drawer-panel absolute right-0 top-0 h-full w-full max-w-[42rem] overflow-y-auto border-l border-stone-200 bg-white shadow-2xl"
                    :class="{ 'task-drawer-panel--open': isOpen }"
                >
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-stone-200 bg-white/95 px-6 py-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Detalle de tarea</p>
                        <button type="button" x-on:click="close()" class="button-secondary py-1.5 text-xs">Cerrar ✕</button>
                    </div>

                    <div class="p-6 lg:p-8">
                        <div x-show="loading" class="flex items-center justify-center py-16">
                            <div class="h-6 w-6 animate-spin rounded-full border-2 border-stone-300" style="border-top-color:var(--brand-amber)"></div>
                        </div>
                        <div x-show="!loading" x-html="content"></div>
                    </div>
                </div>
            </div>

            @auth
                @php
                    $assistantRouteProject = request()->route('project');
                    $assistantContextId = $assistantRouteProject instanceof \App\Models\Project ? $assistantRouteProject->id : null;
                @endphp

                <div
                    x-data="{
                        open: false,
                        loading: false,
                        listening: false,
                        voiceSupported: false,
                        speechLoading: false,
                        audioEnabled: true,
                        message: '',
                        error: '',
                        messages: [],
                        recognition: null,
                        audioPlayer: null,
                        endpoint: @js(route('ai.assistant')),
                        speechEndpoint: @js(route('ai.assistant.speech')),
                        contextType: @js($assistantContextId ? 'project' : null),
                        contextId: @js($assistantContextId),
                        init() {
                            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            this.voiceSupported = Boolean(Recognition);

                            if (!this.voiceSupported) return;

                            this.recognition = new Recognition();
                            this.recognition.lang = 'es-MX';
                            this.recognition.continuous = false;
                            this.recognition.interimResults = false;

                            this.recognition.onstart = () => {
                                this.listening = true;
                                this.error = '';
                            };
                            this.recognition.onend = () => {
                                this.listening = false;
                            };
                            this.recognition.onerror = (event) => {
                                this.listening = false;
                                this.error = event.error === 'not-allowed'
                                    ? 'Activa el permiso del micrófono para dictar.'
                                    : 'No se pudo tomar audio del micrófono.';
                            };
                            this.recognition.onresult = (event) => {
                                const transcript = Array.from(event.results)
                                    .map((result) => result[0]?.transcript || '')
                                    .join(' ')
                                    .trim();

                                if (transcript) {
                                    this.message = [this.message.trim(), transcript].filter(Boolean).join(' ');
                                    this.$nextTick(() => this.$refs.input?.focus());
                                }
                            };
                        },
                        toggle() {
                            this.open = !this.open;
                            if (this.open) this.$nextTick(() => this.$refs.input?.focus());
                        },
                        ask(text) {
                            this.message = text;
                            this.send();
                        },
                        scrollToBottom() {
                            this.$nextTick(() => {
                                if (this.$refs.scroller) this.$refs.scroller.scrollTop = this.$refs.scroller.scrollHeight;
                            });
                        },
                        async send() {
                            const text = this.message.trim();
                            if (!text || this.loading) return;

                            this.messages.push({ role: 'user', text, sources: [] });
                            this.message = '';
                            this.error = '';
                            this.loading = true;
                            this.scrollToBottom();

                            try {
                                const response = await fetch(this.endpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || '',
                                    },
                                    body: JSON.stringify({
                                        message: text,
                                        context_type: this.contextType,
                                        context_id: this.contextId,
                                    }),
                                });

                                const data = await response.json().catch(() => ({}));

                                if (!response.ok) {
                                    throw new Error(data.message || 'No se pudo generar la respuesta.');
                                }

                                this.messages.push({
                                    role: 'assistant',
                                    text: data.answer || 'Sin respuesta.',
                                    sources: data.sources || [],
                                });
                                if (this.audioEnabled) {
                                    this.playAnswer(data.answer || '');
                                }
                                this.scrollToBottom();
                            } catch (error) {
                                this.error = error.message || 'No se pudo generar la respuesta.';
                            } finally {
                                this.loading = false;
                            }
                        },
                        toggleListening() {
                            if (!this.voiceSupported || !this.recognition) {
                                this.error = 'Tu navegador no permite dictado por voz.';
                                return;
                            }

                            if (this.listening) {
                                this.recognition.stop();
                                return;
                            }

                            try {
                                this.error = '';
                                this.recognition.start();
                            } catch (error) {
                                this.error = 'No se pudo iniciar el micrófono.';
                            }
                        },
                        async playAnswer(text) {
                            const spokenText = String(text || '').trim();
                            if (!spokenText || this.speechLoading) return;

                            this.speechLoading = true;
                            this.error = '';

                            try {
                                const response = await fetch(this.speechEndpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'audio/mpeg',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || '',
                                    },
                                    body: JSON.stringify({ text: spokenText }),
                                });

                                if (!response.ok) {
                                    const message = await response.text();
                                    throw new Error(message || 'No se pudo generar el audio.');
                                }

                                const audioUrl = URL.createObjectURL(await response.blob());

                                if (this.audioPlayer) {
                                    this.audioPlayer.pause();
                                    URL.revokeObjectURL(this.audioPlayer.src);
                                }

                                this.audioPlayer = new Audio(audioUrl);
                                this.audioPlayer.onended = () => URL.revokeObjectURL(audioUrl);
                                await this.audioPlayer.play();
                            } catch (error) {
                                this.error = error.message || 'No se pudo reproducir el audio.';
                            } finally {
                                this.speechLoading = false;
                            }
                        },
                    }"
                    x-on:keydown.escape.window="open = false"
                    class="fixed z-50"
                    style="right:1.5rem; bottom:1.5rem"
                >
                    <button
                        type="button"
                        x-show="!open"
                        x-on:click="toggle()"
                        class="button-primary shadow-lg"
                        style="display:none"
                    >
                        Asistente IA
                    </button>

                    <section
                        x-show="open"
                        class="panel flex flex-col"
                        style="display:none; width:min(28rem, calc(100vw - 2rem)); max-height:min(42rem, calc(100vh - 3rem));"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-stone-200 px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em]" style="color:var(--brand-amber)">Bespoke IA</p>
                                <h2 class="mt-1 text-lg font-semibold text-slate-950">Asistente operativo</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" x-on:click="audioEnabled = !audioEnabled" class="button-secondary px-3 py-1.5 text-xs">
                                    <span x-text="audioEnabled ? 'Audio activo' : 'Audio apagado'"></span>
                                </button>
                                <button type="button" x-on:click="open = false" class="button-secondary px-3 py-1.5 text-xs">Cerrar</button>
                            </div>
                        </div>

                        <div x-ref="scroller" class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                            <template x-if="messages.length === 0">
                                <div class="space-y-2">
                                    <button type="button" x-on:click="ask('Dame los principales riesgos operativos de hoy')" class="button-secondary w-full justify-start text-left">Riesgos de hoy</button>
                                    <button type="button" x-on:click="ask('Resume la carga del equipo y quién está sobrecargado')" class="button-secondary w-full justify-start text-left">Carga del equipo</button>
                                    <button type="button" x-on:click="ask('Qué proyectos requieren seguimiento primero')" class="button-secondary w-full justify-start text-left">Seguimiento prioritario</button>
                                </div>
                            </template>

                            <template x-for="(item, index) in messages" :key="index">
                                <div>
                                    <div
                                        class="rounded-2xl border px-4 py-3 text-sm leading-relaxed"
                                        :class="item.role === 'user' ? 'ml-8 border-stone-200 bg-stone-50 text-slate-700' : 'mr-6 border-white bg-white text-slate-700 shadow-sm'"
                                    >
                                        <p class="whitespace-pre-line" x-text="item.text"></p>
                                    </div>

                                    <div x-show="item.role === 'assistant' || (item.sources && item.sources.length)" class="mt-2 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            x-show="item.role === 'assistant'"
                                            x-on:click="playAnswer(item.text)"
                                            class="rounded-full border border-stone-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-stone-300 hover:text-slate-900"
                                        >
                                            Escuchar
                                        </button>
                                        <template x-for="source in item.sources" :key="source.url">
                                            <a
                                                class="rounded-full border border-stone-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-stone-300 hover:text-slate-900"
                                                :href="source.url"
                                                x-text="source.label"
                                            ></a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-show="loading" class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-500" style="display:none">
                                Analizando contexto operativo...
                            </div>
                            <div x-show="speechLoading" class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-500" style="display:none">
                                Generando audio...
                            </div>
                        </div>

                        <form x-on:submit.prevent="send()" class="border-t border-stone-200 p-4">
                            <div x-show="error" class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" style="display:none" x-text="error"></div>

                            <label class="field-label sr-only" for="ai-assistant-message">Pregunta para el asistente</label>
                            <div class="flex items-stretch gap-2">
                                <textarea
                                    id="ai-assistant-message"
                                    x-ref="input"
                                    x-model="message"
                                    rows="3"
                                    class="field mt-0 resize-none"
                                    placeholder="Pregunta por riesgos, cargas, vencimientos o prioridades..."
                                    x-on:keydown.enter.prevent="if (!$event.shiftKey) send(); else message += '\n'"
                                ></textarea>
                                <button
                                    type="button"
                                    x-on:click="toggleListening()"
                                    class="button-secondary shrink-0 justify-center px-0 w-12"
                                    :class="listening ? 'border-rose-200 bg-rose-50 text-rose-700' : ''"
                                    :disabled="!voiceSupported"
                                    :aria-label="listening ? 'Detener dictado' : 'Activar micrófono'"
                                    :title="listening ? 'Detener dictado' : 'Activar micrófono'"
                                >
                                    <svg x-show="!listening" class="h-5 w-5" style="display:none" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-12 0v1.5a6 6 0 0 0 6 6Zm0 0v3.75m-3.75 0h7.5M12 15a3 3 0 0 1-3-3V5.25a3 3 0 1 1 6 0V12a3 3 0 0 1-3 3Z" />
                                    </svg>
                                    <svg x-show="listening" class="h-5 w-5" style="display:none" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M7.5 6.75A2.25 2.25 0 0 1 9.75 4.5h4.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-4.5a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
                                    </svg>
                                    <span class="sr-only" x-text="listening ? 'Detener dictado' : 'Activar micrófono'"></span>
                                </button>
                            </div>

                            <p x-show="!voiceSupported" class="mt-2 text-xs text-slate-500" style="display:none">
                                Dictado no disponible en este navegador.
                            </p>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="text-xs text-slate-500">Consulta datos de Bespoke OS.</p>
                                <button type="submit" class="button-primary" :disabled="loading">
                                    <span x-show="!loading">Enviar</span>
                                    <span x-show="loading" style="display:none">Enviando</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            @endauth
        </div>
    </body>
</html>
