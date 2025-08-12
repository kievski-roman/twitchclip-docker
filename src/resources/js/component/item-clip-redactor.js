document.addEventListener('alpine:init', () => {
    Alpine.data('clipsList', () => ({
        showConfirm: false,
        selectedId: null,
        selectedTitle: '',
        // вместо одного флага — набор id, которые сейчас удаляются
        removingIds: new Set(),

        open(detail) {
            this.selectedId    = detail?.id ?? null;
            this.selectedTitle = detail?.title ?? '';
            this.showConfirm   = true;
        },
        close() {
            this.showConfirm = false;
            this.selectedId = null;
            this.selectedTitle = '';
        },

        async confirmRemove() {
            const id = this.selectedId;
            if (!id || this.removingIds.has(id)) return;

            // закрываем модалку сразу, чтобы не мешала
            this.showConfirm = false;

            // анимация исчезновения строки
            const row = document.querySelector(`[data-clip-id="${id}"]`);
            if (!row) return;

            const placeholder = document.createComment(`clip ${id} placeholder`);
            row.parentNode.insertBefore(placeholder, row);
            row.classList.add('fade-out');
            setTimeout(() => { if (row.isConnected) row.remove(); }, 300);

            // отправляем DELETE
            this.removingIds.add(id);
            try {
                const res = await fetch(`/clips/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                placeholder.remove();
            } catch (e) {
                // откат — вернём строку на место
                if (placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(row, placeholder);
                    row.classList.remove('fade-out');
                    placeholder.remove();
                }
                alert('Не вдалося видалити кліп');
            } finally {
                this.removingIds.delete(id);
                // чистим выбранное (на всякий)
                if (this.selectedId === id) {
                    this.selectedId = null;
                    this.selectedTitle = '';
                }
            }
        },
    }));
    Alpine.data('btnLoader', (action) => ({
        loading: false,
        async run() {
            if (this.loading) return;
            this.loading = true;
            try { await action?.(); }
            finally { this.loading = false; }
        }
    }));
});
