const STATUS = {
    QUEUED: '{{ App\Enums\ClipStatus::QUEUED->value }}',
    READY:  '{{ App\Enums\ClipStatus::READY->value }}',
    PROC:   '{{ App\Enums\ClipStatus::HARD_PROCESSING->value }}',
    DONE:   '{{ App\Enums\ClipStatus::HARD_DONE->value }}',
};

    // Редактор VTT с авто-сохранением и превью
    function vttEditor(saveUrl, initialText) {
    return {
    text: initialText,
    saving: false,
    saved: false,
    timer: null,

    scheduleSave() {
    clearTimeout(this.timer);
    this.timer = setTimeout(() => this.save(), 800);
    this.preview();
},
    async save() {
    this.saving = true; this.saved = false;
    await fetch(saveUrl, {
    method: 'PUT',
    headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': '{{ csrf_token() }}'
},
    body: JSON.stringify({ vtt: this.text })
});
    this.saving = false; this.saved = true;
    window.dispatchEvent(new CustomEvent('vtt-updated'));
    setTimeout(() => this.saved = false, 1500);
},
    preview() {
    const video = this.$refs.player;
    const track = this.$refs.track;
    if (!video || !track) return;
    const blob = new Blob([this.text], { type: 'text/vtt' });
    track.src = URL.createObjectURL(blob);
    const [tt] = video.textTracks;
    if (tt) {
    tt.mode = 'disabled';
    tt.mode = 'showing';
}
}
}
}

    // Управление генерацией харда и стилями
    function hardSub(id, genUrl, dlUrl, statusUrl, styleUrl, csrf, initialStyle) {
    const defaults = {
    color:      '#ffff00',
    fontSize:   24,
    outline:   '#000000',
    fontStyle:  'normal',
};

    return {
    status: STATUS.READY,
    downloadUrl: dlUrl,
    style: { ...defaults, ...initialStyle },
    poller: null,

    init() {
    // Live-preview в видео
    this.applyCueStyle();
    this.$watch('style.color',      () => this.applyCueStyle());
    this.$watch('style.fontSize',   () => this.applyCueStyle());
    this.$watch('style.outline', () => this.applyCueStyle());
    this.$watch('style.fontStyle',  () => this.applyCueStyle());

    // Сохраняем новые стили на бэке
    this.$watch('style', () => this.saveStyle(), { deep: true });

    window.addEventListener('vtt-updated', () => {
    this.status = STATUS.READY;
    this.downloadUrl = null;
});
    if (this.status === STATUS.PROC) {
    this.startPolling();
}
},

    async saveStyle() {
    this.status = STATUS.READY;
    this.downloadUrl = null;
    await fetch(styleUrl, {
    method: 'PATCH',
    headers: {
    'X-CSRF-TOKEN': csrf,
    'Content-Type': 'application/json'
},
    body: JSON.stringify({ style: this.style })
});
},

    applyCueStyle() {
    const { color, fontSize, outline, fontStyle } = this.style;
    let el = document.getElementById('cueStyle');
    if (!el) {
    el = document.createElement('style');
    el.id = 'cueStyle';
    document.head.appendChild(el);
}
    el.textContent = `
video::cue {
  color: ${color};
  font-size: ${fontSize}px;
  font-style: ${fontStyle.includes('italic') ? 'italic' : 'normal'};
  font-weight: ${fontStyle.includes('bold')   ? 'bold'    : 'normal'};
  background: transparent !important;
  text-shadow:
    -1px -1px 0 ${outline},
     1px -1px 0 ${outline},
    -1px  1px 0 ${outline},
     1px  1px 0 ${outline};
}
            `;
},

    async generate() {
    this.status = STATUS.PROC;
    this.startPolling();
    await fetch(genUrl, {
    method: 'POST',
    headers: {
    'X-CSRF-TOKEN': csrf,
    'Content-Type': 'application/json'
},
    body: JSON.stringify({ style: this.style })
});
},

    startPolling() {
    if (this.poller) return;
    this.poller = setInterval(async () => {
    const res = await fetch(statusUrl).then(r => r.json());
    this.status = res.status;
    this.downloadUrl = res.url;
    if (this.status === STATUS.DONE) {
    clearInterval(this.poller);
    this.poller = null;
}
}, 2000);
}
}
}
