document.addEventListener('alpine:init', () => {
    Alpine.data('clipRow', (clipId) => ({
        removing: false,

        async remove() {
            if (this.removing) return;
            if (!confirm('Точно видалити кліп?')) return;

            this.removing = true;

            const res = await fetch(`/clips/${clipId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!res.ok) {
                this.removing = false;
                alert('Не вдалося видалити кліп');
                return;
            }

            // Удаляем элемент из DOM
            this.$root.remove();
        }
    }));
});
