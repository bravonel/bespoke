const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-open-modal]');

    if (openTrigger) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: openTrigger.dataset.openModal }));
    }

    const closeTrigger = event.target.closest('[data-close-modal]');

    if (closeTrigger) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: closeTrigger.dataset.closeModal }));
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('taskDrawer', () => ({
        isOpen: false,
        loading: false,
        content: '',

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
            this.isOpen = false;
            this.content = '';
            document.body.classList.remove('overflow-hidden');
        },
    }));
});

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
            const payload = {
                status: targetColumn.dataset.status,
                ordered_ids: getCards(targetColumn).map((taskCard) => Number(taskCard.dataset.taskId)),
            };

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
                throw new Error('No se pudo mover la tarjeta.');
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
