document.addEventListener('alpine:init', () => {
    Alpine.data('clipsList', () => ({
        showConfirm: false,
        removing: false,
        selectedId: null,
        selectedTitle: '',

        open(detail) {
            this.selectedId    = detail?.id ?? null;
            this.selectedTitle = detail?.title ?? '';
            this.showConfirm   = true;
        },
        close() {
            if (this.removing) return;
            this.showConfirm = false;
            this.selectedId = null;
            this.selectedTitle = '5';
        },

        async confirmRemove() {
            if (!this.selectedId || this.removing) return;

            // 1) мгновенно закрываем модалку (элемент будет ВЫБРОШЕН из DOM)
            this.showConfirm = false;

            // 2) находим <li> и запускаем класс-анимацию
            const row = document.querySelector(`[data-clip-id="${this.selectedId}"]`);
            if (!row) return;

            const placeholder = document.createComment(`clip ${this.selectedId} placeholder`);
            row.parentNode.insertBefore(placeholder, row);

            row.classList.add('fade-out');

            // снимаем из DOM через 300мс
            setTimeout(() => { if (row.isConnected) row.remove(); }, 300);

            // 3) параллельно шлём DELETE
            this.removing = true;
            try {
                const res = await fetch(`/clips/${this.selectedId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                placeholder.remove();
                this.selectedId = null;
                this.selectedTitle = '';
            } catch (e) {
                // ОТКАТ: вернём строку на место и уберём эффект
                if (placeholder.parentNode) {
                    placeholder.parentNode.insertBefore(row, placeholder);
                    row.classList.remove('fade-out');
                    placeholder.remove();
                }
                alert('Не вдалося видалити кліп');
            } finally {
                this.removing = false;
            }
        },
    }));
});
