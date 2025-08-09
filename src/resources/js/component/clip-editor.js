// resources/js/clip-page.js
document.addEventListener('alpine:init', () => {
    // Инлайн-редактор названия в списке (твой текущий45555555555555 li)
    Alpine.data('clipTitleEditor', (clipId, initialTitle, csrf) => ({
        editing: false,
        saving: false,
        tempTitle: initialTitle,

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const res = await fetch(`/clips/${clipId}/title`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ name_video: this.tempTitle })
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.editing = false;
            } catch (e) {
                alert('Не вдалося зберегти назву');
            } finally {
                this.saving = false;
            }
        }
    }));

    // Главный компонент страницы клипа
    Alpine.data('clipEditor', (opts) => ({
        // ---- props ----
        csrf:  opts.csrf,
        urls:  opts.urls,
        STATUS: opts.STATUS,

        // ---- title on page header ----
        title: opts.initial.title,
        titleEditing: false,
        titleSaving: false,

        async savePageTitle() {
            if (this.titleSaving) return;
            this.titleSaving = true;
            try {
                const res = await fetch(this.urls.updateTitle, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf
                    },
                    body: JSON.stringify({ name_video: this.title })
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.titleEditing = false;
            } catch {
                alert('Не вдалося зберегти назву');
            } finally {
                this.titleSaving = false;
            }
        },

        // ---- video / vtt ----
        playerEl: null,
        trackEl: null,
        duration: 0,

        text: opts.initial.vttText,
        saving: false,
        saved: false,
        debounce: null,

        scheduleSave() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.saveVtt(), 800);
            this.previewVtt(); // live превью
        },
        async saveVtt() {
            this.saving = true; this.saved = false;
            await fetch(this.urls.saveVtt, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ vtt: this.text })
            });
            this.saving = false; this.saved = true;
            setTimeout(() => this.saved = false, 1500);
        },

        // ---- styles / hard-sub ----
        style: Object.assign({
            color:'#ffff00', fontSize:24, outline:'#000000', fontStyle:'normal', ratio:'16:9'
        }, opts.initial.style || {}),
        status: opts.STATUS.READY,
        downloadUrl: opts.urls.download || null,
        poller: null,

        async saveStyle() {
            this.status = this.STATUS.READY; this.downloadUrl = null;
            await fetch(this.urls.saveStyle, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Content-Type': 'application/json' },
                body: JSON.stringify({ style: this.style })
            });
            this.applyCueStyle();
        },
        applyCueStyle() {
            const { color, fontSize, outline, fontStyle } = this.style;
            let el = document.getElementById('cueStyle');
            if (!el) { el = document.createElement('style'); el.id = 'cueStyle'; document.head.appendChild(el); }
            el.textContent =
                `video::cue{
  color:${color};
  font-size:${fontSize}px;
  font-style:${fontStyle.includes('italic')?'italic':'normal'};
  font-weight:${fontStyle.includes('bold')?'bold':'normal'};
  background:transparent!important;
  text-shadow:-1px -1px 0 ${outline},1px -1px 0 ${outline},-1px 1px 0 ${outline},1px 1px 0 ${outline};
}`;
        },
        async generate() {
            this.status = this.STATUS.PROC;
            this.startPolling();
            await fetch(this.urls.gen, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    style: this.style,
                    ratio: this.style.ratio,
                    trim: { start: this.trim.start, end: this.trim.end ?? this.duration }
                })
            });
        },
        startPolling() {
            if (this.poller) return;
            this.poller = setInterval(async () => {
                const res = await fetch(this.urls.status).then(r => r.json());
                this.status = res.status;
                this.downloadUrl = res.url;
                if (this.status === this.STATUS.DONE) { clearInterval(this.poller); this.poller = null; }
            }, 2000);
        },

        // ---- trim timeline ----
        trim: { start: 0, end: 0 },

        get rangeFillStyle() {
            const d = this.duration || 1;
            const left = Math.min(this.trim.start, this.trim.end) / d * 100;
            const right = 100 - (Math.max(this.trim.start, this.trim.end) / d * 100);
            return `left:${left}%; right:${right}%`;
        },
        onStartChange() {
            if (this.trim.start > this.trim.end) this.trim.end = this.trim.start;
            if (this.trim.start < 0) this.trim.start = 0;
            this.previewVtt();
        },
        onEndChange() {
            if (this.trim.end < this.trim.start) this.trim.start = this.trim.end;
            if (this.trim.end > this.duration) this.trim.end = this.duration;
            this.previewVtt();
        },

        previewTrim() {
            if (!this.playerEl) return;
            const start = this.trim.start, end = this.trim.end || this.duration;
            this.playerEl.currentTime = start;
            this.playerEl.play();
            const stop = () => {
                if (this.playerEl.currentTime >= end) {
                    this.playerEl.pause();
                    this.playerEl.removeEventListener('timeupdate', stop);
                }
            };
            this.playerEl.addEventListener('timeupdate', stop);
        },
        resetTrim() {
            this.trim.start = 0;
            this.trim.end = this.duration || 0;
            this.previewVtt();
        },

        // ---- VTT preview with trim shift ----
        previewVtt() {
            if (!this.playerEl || !this.trackEl) return;
            const start = this.trim.start;
            const end   = this.trim.end || this.duration || start;
            const vtt = this.buildTrimmedVtt(this.text, start, end);
            const blob = new Blob([vtt], { type: 'text/vtt' });
            this.trackEl.src = URL.createObjectURL(blob);
            const [tt] = this.playerEl.textTracks;
            if (tt) { tt.mode = 'disabled'; tt.mode = 'showing'; }
        },
        buildTrimmedVtt(src, start, end) {
            const lines = src.split(/\r?\n/);
            const out = ['WEBVTT', ''];
            const tsToSec = (ts) => {
                const [hms, ms='0'] = ts.split('.');
                const [h, m, s] = hms.split(':').map(Number);
                return h*3600 + m*60 + s + (+ms)/1000;
            };
            const sec = (n) => {
                if (n < 0) n = 0;
                const ms = Math.round((n - Math.floor(n)) * 1000);
                let t = Math.floor(n);
                const s = t % 60; t = (t - s)/60;
                const m = t % 60; const h = (t - m)/60;
                return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}.${String(ms).padStart(3,'0')}`;
            };
            for (let i = 0; i < lines.length; i++) {
                const m = lines[i].match(/^(\d{2}:\d{2}:\d{2}\.\d{3})\s-->\s(\d{2}:\d{2}:\d{2}\.\d{3})/);
                if (!m) continue;
                const from = tsToSec(m[1]), to = tsToSec(m[2]);
                if (to < start || from > end) { while (i < lines.length && lines[i].trim() !== '') i++; continue; }
                const nf = Math.max(from, start) - start;
                const nt = Math.min(to, end) - start;
                out.push(`${sec(nf)} --> ${sec(nt)}`);
                let j = i + 1;
                while (j < lines.length && lines[j].trim() !== '') { out.push(lines[j]); j++; }
                out.push('');
                i = j;
            }
            return out.join('\n');
        },

        // ---- lifecycle ----
        init() {
            this.playerEl = this.$refs.player;
            this.trackEl  = this.$refs.track;
            this.duration = this.playerEl?.duration || 0;
            // если duration придёт позже
            this.playerEl?.addEventListener('loadedmetadata', () => {
                this.duration = this.playerEl.duration || 0;
                if (!this.trim.end || this.trim.end === 0) this.trim.end = this.duration;
                this.previewVtt();
            });
            this.applyCueStyle();
            this.previewVtt();
            this.$watch('style', () => { if (this.status !== this.STATUS.PROC) this.saveStyle(); }, { deep: true });
        },
    }));
});
