import QRCodeStyling from 'qr-code-styling';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

window.qrDesigner = (initial = {}) => ({
    state: {
        foreground: initial.design?.foreground || '#161616',
        background: initial.design?.background || '#FFFFFF',
        dots: initial.design?.dots || 'rounded',
        corners: initial.design?.corners || 'extra-rounded',
        frame: initial.design?.frame || 'soft',
        cta: initial.design?.cta || 'ESCANEA AQUÍ',
    },
    data: initial.url || `${window.location.origin}/q/preview`,
    logo: initial.logo || null,
    qr: null,
    copied: false,
    init() {
        this.qr = new QRCodeStyling(this.options());
        this.qr.append(this.$refs.canvas);
        this.$watch('state', () => this.render());
    },
    options(size = 320) {
        return {
            width: size,
            height: size,
            type: 'svg',
            data: this.data,
            image: this.logo || undefined,
            margin: 12,
            qrOptions: { errorCorrectionLevel: 'H' },
            imageOptions: { crossOrigin: 'anonymous', margin: 8, imageSize: 0.34, hideBackgroundDots: true },
            dotsOptions: { color: this.state.foreground, type: this.state.dots },
            backgroundOptions: { color: this.state.background },
            cornersSquareOptions: { color: this.state.foreground, type: this.state.corners },
            cornersDotOptions: { color: this.state.foreground, type: this.state.corners === 'dot' ? 'dot' : 'square' },
        };
    },
    render() {
        this.qr?.update(this.options());
    },
    loadLogo(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            this.logo = reader.result;
            this.render();
        };
        reader.readAsDataURL(file);
    },
    removeLogo() {
        this.logo = null;
        if (this.$refs.logoInput) this.$refs.logoInput.value = '';
        this.render();
    },
    download(extension = 'png') {
        this.qr?.download({ name: initial.filename || 'bespoke-qr', extension });
    },
    async copyUrl() {
        await navigator.clipboard.writeText(this.data);
        this.copied = true;
        window.setTimeout(() => { this.copied = false; }, 1600);
    },
});

window.qrMini = (config = {}) => ({
    init() {
        const qr = new QRCodeStyling({
            width: 104,
            height: 104,
            type: 'svg',
            data: config.url,
            image: config.logo || undefined,
            margin: 4,
            qrOptions: { errorCorrectionLevel: 'H' },
            imageOptions: { crossOrigin: 'anonymous', margin: 3, imageSize: 0.32, hideBackgroundDots: true },
            dotsOptions: { color: config.design?.foreground || '#161616', type: config.design?.dots || 'rounded' },
            backgroundOptions: { color: config.design?.background || '#FFFFFF' },
            cornersSquareOptions: { color: config.design?.foreground || '#161616', type: config.design?.corners || 'extra-rounded' },
        });
        qr.append(this.$refs.canvas);
    },
});

window.renderPrintableQr = (element, config = {}) => {
    if (!element) return null;

    const design = config.design || {};
    const qr = new QRCodeStyling({
        width: 640,
        height: 640,
        type: 'svg',
        data: config.url,
        image: config.logo || undefined,
        margin: 20,
        qrOptions: { errorCorrectionLevel: 'H' },
        imageOptions: { crossOrigin: 'anonymous', margin: 12, imageSize: 0.34, hideBackgroundDots: true },
        dotsOptions: { color: design.foreground || '#161616', type: design.dots || 'rounded' },
        backgroundOptions: { color: design.background || '#FFFFFF' },
        cornersSquareOptions: { color: design.foreground || '#161616', type: design.corners || 'extra-rounded' },
        cornersDotOptions: { color: design.foreground || '#161616', type: design.corners === 'dot' ? 'dot' : 'square' },
    });

    qr.append(element);

    return qr;
};

window.utmBuilder = (initial = {}) => ({
    open: Boolean(initial.enabled),
    enabled: Boolean(initial.enabled),
    baseUrl: initial.baseUrl || '',
    source: initial.utm_source || '',
    medium: initial.utm_medium || '',
    campaign: initial.utm_campaign || '',
    term: initial.utm_term || '',
    content: initial.utm_content || '',
    custom: Array.isArray(initial.custom) ? initial.custom : [],
    addCustom() {
        if (this.custom.length >= 10) return;
        this.custom.push({ key: '', value: '' });
        this.$nextTick(() => this.$refs.customList?.querySelector('input:last-of-type')?.focus());
    },
    removeCustom(index) {
        this.custom.splice(index, 1);
    },
    previewUrl() {
        const value = String(this.baseUrl || '').trim();
        if (!value || !this.enabled) return value || 'Ingresa primero el vínculo de destino';

        try {
            const url = new URL(value);
            const standard = {
                utm_source: this.source,
                utm_medium: this.medium,
                utm_campaign: this.campaign,
                utm_term: this.term,
                utm_content: this.content,
            };

            Object.entries(standard).forEach(([key, parameterValue]) => {
                const cleanValue = String(parameterValue || '').trim();
                if (cleanValue) url.searchParams.set(key, cleanValue);
                else url.searchParams.delete(key);
            });

            this.custom.forEach((parameter) => {
                const key = String(parameter.key || '').trim();
                const parameterValue = String(parameter.value || '').trim();
                if (key && parameterValue) url.searchParams.set(key, parameterValue);
            });

            return url.toString();
        } catch {
            return value;
        }
    },
});

const activityTracker = (() => {
    const events = [];
    const uiEndpoint = '/activity/ui-events';
    const heartbeatEndpoint = '/activity/heartbeat';
    let lastInteractionAt = Date.now();

    const context = () => ({
        page: document.body.dataset.activityPage || window.location.pathname,
        project_id: Number(document.body.dataset.activityProjectId) || null,
    });

    const track = (eventName, target = null, metadata = {}) => {
        if (!eventName) return;

        events.push({
            event_name: eventName,
            target,
            metadata,
            occurred_at: new Date().toISOString(),
            ...context(),
        });

        if (events.length >= 20) flush();
    };

    const payload = () => ({ _token: csrfToken, events: events.splice(0, 50) });

    const flush = (beacon = false) => {
        if (!events.length) return;

        const body = payload();

        if (beacon && navigator.sendBeacon) {
            navigator.sendBeacon(uiEndpoint, new Blob([JSON.stringify(body)], { type: 'application/json' }));
            return;
        }

        fetch(uiEndpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ?? '',
            },
            body: JSON.stringify(body),
            keepalive: true,
        }).catch(() => {});
    };

    const heartbeat = () => {
        const active = document.visibilityState === 'visible'
            && Date.now() - lastInteractionAt < 90000;

        fetch(heartbeatEndpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ?? '',
            },
            body: JSON.stringify({ active, page: context().page }),
            keepalive: true,
        }).catch(() => {});
    };

    ['click', 'keydown', 'pointerdown', 'scroll'].forEach((name) => {
        document.addEventListener(name, () => { lastInteractionAt = Date.now(); }, { passive: true });
    });

    window.setInterval(() => flush(), 5000);
    window.setInterval(heartbeat, 60000);
    window.addEventListener('pagehide', () => flush(true));

    return { track, flush, heartbeat };
})();

window.bespokeActivity = activityTracker;

let lastTrackedPage = null;
const trackPageView = () => {
    const page = document.body.dataset.activityPage;
    const key = `${page}:${window.location.pathname}${window.location.search}`;

    if (!page || key === lastTrackedPage) return;

    lastTrackedPage = key;
    const eventName = {
        dashboard: 'dashboard.viewed',
        'projects.show': 'project.viewed',
        'tasks.show': 'task.detail_viewed',
    }[page];

    if (eventName) activityTracker.track(eventName, window.location.pathname);
};

document.addEventListener('DOMContentLoaded', trackPageView);
document.addEventListener('livewire:navigated', trackPageView);

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-open-modal]');

    if (openTrigger) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: openTrigger.dataset.openModal }));
        activityTracker.track('modal.opened', openTrigger.dataset.openModal);
    }

    const closeTrigger = event.target.closest('[data-close-modal]');

    if (closeTrigger) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: closeTrigger.dataset.closeModal }));
    }

    const activityTarget = event.target.closest('[data-activity]');

    if (activityTarget) {
        activityTracker.track(activityTarget.dataset.activity, activityTarget.dataset.activityTarget || null);
    }

    const navigation = event.target.closest('nav a[href]');

    if (navigation) {
        activityTracker.track('navigation.clicked', new URL(navigation.href).pathname);
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'get') return;

    const data = new FormData(form);
    const names = [...new Set([...data.keys()].filter((name) => name !== '_token'))];
    activityTracker.track(data.has('q') ? 'search.performed' : 'filter.applied', form.action, {
        filter_names: names,
    });
});

const openRequestedProjectModal = () => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('edit') === '1') {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-project' }));
    }
};

document.addEventListener('alpine:init', () => {
    Alpine.data('taskDrawer', () => ({
        isOpen: false,
        loading: false,
        content: '',

        init() {
            this.$root.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-drawer-subtask-form]');

                if (!form) return;

                event.preventDefault();
                this.updateSubtask(form);
            });
        },

        async open(url) {
            this.isOpen = true;
            this.loading = true;
            this.content = '';
            document.body.classList.add('overflow-hidden');

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Drawer': '1',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                });
                this.content = await response.text();
            } finally {
                this.loading = false;
            }
        },

        close() {
            activityTracker.track('task.drawer_closed', 'task-drawer');
            this.isOpen = false;
            this.content = '';
            document.body.classList.remove('overflow-hidden');
        },

        async updateSubtask(form) {
            const button = form.querySelector('[data-subtask-toggle]');
            const error = this.$root.querySelector('[data-drawer-checklist-error]');
            button.disabled = true;
            button.classList.add('opacity-50');
            error?.classList.add('hidden');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error('No se pudo actualizar la lista. Intenta de nuevo.');
                }

                const payload = await response.json();
                const isDone = Boolean(payload.subtask?.is_done);
                const hiddenInput = form.querySelector('input[name="is_done"]');
                const title = form.querySelector('[data-subtask-title]');
                const count = this.$root.querySelector('[data-drawer-progress-count]');
                const progress = this.$root.querySelector('[data-drawer-progress-bar]');

                hiddenInput.value = isDone ? '0' : '1';
                button.classList.toggle('border-emerald-500', isDone);
                button.classList.toggle('bg-emerald-500', isDone);
                button.classList.toggle('text-white', isDone);
                button.classList.toggle('border-stone-300', !isDone);
                button.classList.toggle('bg-white', !isDone);
                button.classList.toggle('text-transparent', !isDone);
                button.setAttribute('aria-label', isDone ? 'Marcar como pendiente' : 'Marcar como lista');
                title?.classList.toggle('text-slate-400', isDone);
                title?.classList.toggle('line-through', isDone);
                title?.classList.toggle('text-slate-700', !isDone);

                if (count) count.textContent = `${payload.progress.completed}/${payload.progress.total}`;
                if (progress) progress.style.width = `${payload.progress.percentage}%`;
            } catch (exception) {
                if (error) {
                    error.textContent = exception.message;
                    error.classList.remove('hidden');
                }
            } finally {
                button.disabled = false;
                button.classList.remove('opacity-50');
            }
        },
    }));
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', openRequestedProjectModal);
} else {
    window.setTimeout(openRequestedProjectModal, 0);
}

document.addEventListener('livewire:navigated', openRequestedProjectModal);

const initializeTaskBoards = () => {
    document.querySelectorAll('[data-task-board]').forEach((board) => {
        let draggedCard = null;
        let sourceColumn = null;

        const columns = Array.from(board.querySelectorAll('[data-board-column]'));

        const getList = (column) => column.querySelector('[data-column-list]');
        const getCards = (column) => Array.from(column.querySelectorAll('[data-task-card]'));

        const updateColumnState = () => {
            columns.forEach((column) => {
                const taskCount = getCards(column).length;
                column.querySelector('[data-column-count]')?.replaceChildren(document.createTextNode(String(taskCount)));
                column.querySelector('[data-empty-state]')?.classList.toggle('hidden', taskCount > 0);
            });
        };

        const getDragAfterElement = (list, y) => {
            const cards = [...list.querySelectorAll('[data-task-card]:not(.is-dragging)')];

            return cards.reduce((closest, card) => {
                const box = card.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: card };
                }

                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
        };

        const syncBoard = async (card, targetColumn) => {
            const sourceStatus = sourceColumn?.dataset.status;
            const targetStatus = targetColumn.dataset.status;
            const payload = {
                status: targetStatus,
                ordered_ids: getCards(targetColumn).map((taskCard) => Number(taskCard.dataset.taskId)),
            };

            if (targetStatus === 'blocked' && sourceStatus !== 'blocked') {
                const reason = window.prompt('¿Qué impide avanzar y quién debe destrabarlo?');

                if (!reason?.trim()) {
                    throw new Error('El motivo de bloqueo es obligatorio.');
                }

                payload.blocked_reason = reason.trim();
            }

            if (['done', 'finalized'].includes(sourceStatus) && !['done', 'finalized'].includes(targetStatus)) {
                const reason = window.prompt('¿Qué debe corregirse para devolver esta tarea?');

                if (!reason?.trim()) {
                    throw new Error('El motivo de devolución es obligatorio.');
                }

                payload.return_reason = reason.trim();
            }

            if (sourceColumn && sourceColumn !== targetColumn) {
                payload.source_status = sourceColumn.dataset.status;
                payload.source_ordered_ids = getCards(sourceColumn).map((taskCard) => Number(taskCard.dataset.taskId));
            }

            const response = await fetch(card.dataset.moveUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const responsePayload = await response.json().catch(() => ({}));
                const firstError = Object.values(responsePayload.errors ?? {}).flat()[0];
                throw new Error(firstError || responsePayload.message || 'No se pudo mover la tarjeta.');
            }
        };

        columns.forEach((column) => {
            const list = getList(column);

            list.addEventListener('dragover', (event) => {
                if (!draggedCard) {
                    return;
                }

                event.preventDefault();
                column.classList.add('is-drop-target');

                const nextCard = getDragAfterElement(list, event.clientY);

                if (!nextCard) {
                    list.appendChild(draggedCard);
                } else {
                    list.insertBefore(draggedCard, nextCard);
                }

                updateColumnState();
            });

            list.addEventListener('drop', async (event) => {
                event.preventDefault();

                if (!draggedCard) {
                    return;
                }

                const targetColumn = draggedCard.closest('[data-board-column]');

                try {
                    await syncBoard(draggedCard, targetColumn);
                } catch (error) {
                    window.alert(error.message);
                    window.location.reload();
                } finally {
                    columns.forEach((item) => item.classList.remove('is-drop-target'));
                    draggedCard.classList.remove('is-dragging');
                    draggedCard = null;
                    sourceColumn = null;
                    updateColumnState();
                }
            });

            list.addEventListener('dragleave', (event) => {
                if (event.relatedTarget instanceof Node && event.currentTarget.contains(event.relatedTarget)) {
                    return;
                }

                column.classList.remove('is-drop-target');
            });
        });

        board.querySelectorAll('[data-task-card]').forEach((card) => {
            card.addEventListener('dragstart', () => {
                draggedCard = card;
                sourceColumn = card.closest('[data-board-column]');
                card.classList.add('is-dragging');
            });

            card.addEventListener('dragend', () => {
                columns.forEach((column) => column.classList.remove('is-drop-target'));
                card.classList.remove('is-dragging');
                updateColumnState();
            });

            const opener = card.querySelector('[data-open-task]');

            opener?.addEventListener('click', () => {
                activityTracker.track('task.drawer_opened', `task:${card.dataset.taskId}`);
                if (draggedCard) {
                    return;
                }

                const detailUrl = card.dataset.detailUrl;

                if (detailUrl) {
                    window.dispatchEvent(
                        new CustomEvent('open-task-drawer', { detail: { url: detailUrl } })
                    );
                }
            });
        });

        updateColumnState();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTaskBoards);
} else {
    initializeTaskBoards();
}

document.addEventListener('livewire:navigated', initializeTaskBoards);
