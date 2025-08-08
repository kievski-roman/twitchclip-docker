document.addEventListener('alpine:init', () => {
    Alpine.data('clipTitleEditor', (clipId, initialTitle) => ({
        editing: false,
        tempTitle: initialTitle,

        async save() {
            const res = await fetch(`/clips/${clipId}/title`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ name_video: this.tempTitle })
            });

            if (!res.ok) {
                alert('Помилка при збереженні назви');
                return;
            }

            const json = await res.json();
            this.tempTitle = json.name_video;
            this.editing = false;
        }
    }));

});
