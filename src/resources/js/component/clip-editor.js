// resources/js/clip-editor.js
document.addEventListener('alpine:init', () => {
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
            } catch {
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
        segmentPlayback: { active: true, loop: false },


        // ---- заголовок страницы ----
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

        // Полный VTT храним тут
        sourceVtt: opts.initial.vttText,

        // textarea: показываем только сегмент (без WEBVTT)
        showSegmentOnly: true,
        text: '',

        saving: false,
        saved: false,
        debounce: null,

        scheduleSave() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.saveVtt(), 800);
            this.previewVtt(); // обновляем яркое превью
        },

        async saveVtt() {
            this.saving = true; this.saved = false;
            let toSave = this.text;

            if (this.showSegmentOnly) {
                const start = this.trim.start;
                const end   = this.trim.end || this.duration || start;
                // парсим сегмент из textarea (тайминги от 0)
                const segCues = this.parseVtt(this.text);
                // сдвигаем назад в абсолютные тайминги
                const restored = segCues.map(c => ({ ...c, from: c.from + start, to: c.to + start }));
                // мерджим в полный VTT
                const full = this.parseVtt(this.sourceVtt);
                const merged = this.mergeSegmentBack(full, restored, start, end);
                toSave = this.serializeFull(merged);
                this.sourceVtt = toSave;
            } else {
                // редактировали полный текст
                this.sourceVtt = this.text;
            }

            await fetch(this.urls.saveVtt, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ vtt: toSave })
            });

            this.saving = false; this.saved = true;
            setTimeout(() => this.saved = false, 1500);

            // обновим превью сегмента в плеере
            this.previewVtt();
        },

        // ---- стили / генерация ----
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
            this.renderTextareaForSegment();
            this.previewVtt();
            if (this.playerEl) this.playerEl.currentTime = this.trim.start;
        },
        onEndChange() {
            if (this.trim.end < this.trim.start) this.trim.start = this.trim.end;
            if (this.trim.end > this.duration) this.trim.end = this.duration;
            this.renderTextareaForSegment();
            this.previewVtt();
            if (this.playerEl && this.playerEl.currentTime > this.trim.end) {
                this.playerEl.currentTime = this.trim.end;
            }
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
            this.renderTextareaForSegment();
            this.previewVtt();
        },
        serializeSegmentForTrack(cues, start, end) {
            const out = ['WEBVTT',''];
            for (const c of cues) {
                if (c.to <= start || c.from >= end) continue;
                const nf = Math.max(c.from, start); // БЕЗ "- start"
                const nt = Math.min(c.to,   end);
                out.push(`${this.secFmt(nf)} --> ${this.secFmt(nt)}${c.settings ? ' ' + c.settings : ''}`);
                if (c.text) out.push(...c.text.split('\n'));
                out.push('');
            }
            return out.join('\n');
        },

        // ---- яркое превью: строим сегмент с заголовком ----
        previewVtt() {
            if (!this.playerEl || !this.trackEl) return;
            const start = this.trim.start;
            const end   = this.trim.end || this.duration || start;
            const cues  = this.parseVtt(this.sourceVtt);
            const vtt   = this.serializeSegmentForTrack(cues, start, end); // <-- тут
            const blob  = new Blob([vtt], { type:'text/vtt' });
            this.trackEl.src = URL.createObjectURL(blob);
            const [tt] = this.playerEl.textTracks;
            if (tt) { tt.mode = 'disabled'; tt.mode = 'showing'; }
        },

        // ---- textarea: только сегмент, без WEBVTT; маска таймингов ----
        renderTextareaForSegment() {
            if (!this.showSegmentOnly) { this.text = this.sourceVtt; return; }
            const start = this.trim.start;
            const end   = this.trim.end || this.duration || start;
            this.text   = this.serializeSegment(this.parseVtt(this.sourceVtt), start, end, /*hideHeader*/true);
        },
        onTextareaInput(e) {
            const val = e.target.value;
            const fixed = val.replace(
                /(\d{1,2}):?(\d{1,2})?:?(\d{1,2})?[\.,]?(\d{0,3})\s*-->\s*(\d{1,2}):?(\d{1,2})?:?(\d{1,2})?[\.,]?(\d{0,3})/g,
                (_,h1,m1='0',s1='0',ms1='0',h2,m2='0',s2='0',ms2='0') => {
                    const A = this.normTime(h1,m1,s1,ms1), B = this.normTime(h2,m2,s2,ms2);
                    return `${A} --> ${B}`;
                }
            );
            if (fixed !== val) {
                const pos = e.target.selectionStart;
                this.text = fixed;
                this.$nextTick(() => e.target.setSelectionRange(pos,pos));
            } else {
                this.text = val;
            }
            this.scheduleSave();
        },
        normTime(h,m,s,ms){
            const H = String(Math.min(99, parseInt(h||'0',10))).padStart(2,'0');
            const M = String(Math.min(59, parseInt(m||'0',10))).padStart(2,'0');
            const S = String(Math.min(59, parseInt(s||'0',10))).padStart(2,'0');
            const MS= String(Math.min(999,parseInt((ms||'0').padEnd(3,'0'),10))).padStart(3,'0');
            return `${H}:${M}:${S}.${MS}`;
        },

        // ---- VTT helpers (с сохранением settings) ----
        parseVtt(text){
            const lines = text.replace(/^\uFEFF/,'').split(/\r?\n/);
            const cues = [];
            const tsToSec = (ts)=>{
                const m = ts.match(/^(\d{2}):(\d{2}):(\d{2})[.,](\d{3})$/);
                if (!m) return 0;
                const [,h,mn,s,ms] = m.map(Number);
                return h*3600 + mn*60 + s + ms/1000;
            };
            let i = (lines[0]||'').trim().toUpperCase()==='WEBVTT' ? 1 : 0;

            while (i < lines.length) {
                while (i<lines.length && lines[i].trim()==='') i++;
                let id = null;
                if (i<lines.length && !/-->/.test(lines[i]) && lines[i].trim()!=='') { id = lines[i].trim(); i++; }

                const m = lines[i]?.match(/^(\d{2}:\d{2}:\d{2}[.,]\d{3})\s-->\s(\d{2}:\d{2}:\d{2}[.,]\d{3})(.*)?$/);
                if (!m) { i++; continue; }
                const from = tsToSec(m[1].replace(',', '.'));
                const to   = tsToSec(m[2].replace(',', '.'));
                const settings = (m[3]||'').trim();
                i++;

                const payload = [];
                while (i<lines.length && lines[i].trim()!=='') { payload.push(lines[i]); i++; }
                while (i<lines.length && lines[i].trim()==='') i++;

                cues.push({ id, from, to, text: payload.join('\n'), settings });
            }
            return cues.sort((a,b)=>a.from-b.from);
        },

        secFmt(n){
            if (n < 0) n = 0;
            let ms = Math.round((n % 1) * 1000);
            let t = Math.floor(n);
            let s = t % 60; t = (t - s)/60;
            let m = t % 60; let h = (t - m)/60;
            if (ms === 1000) { ms = 0; s += 1; if (s===60){s=0; m+=1; if(m===60){m=0; h+=1;} } }
            return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}.${String(ms).padStart(3,'0')}`;
        },

        serializeSegment(cues, start, end, hideHeader){
            const out = hideHeader ? [] : ['WEBVTT',''];
            for (const c of cues) {
                if (c.to <= start || c.from >= end) continue;
                const nf = Math.max(c.from, start) - start; // обрезаем и сдвигаем к нулю
                const nt = Math.min(c.to,   end)   - start;
                out.push(`${this.secFmt(nf)} --> ${this.secFmt(nt)}${c.settings ? ' ' + c.settings : ''}`);
                if (c.text) out.push(...c.text.split('\n'));
                out.push('');
            }
            return out.join('\n');
        },

        serializeFull(cues){
            const out = ['WEBVTT',''];
            for (const c of cues.sort((a,b)=>a.from-b.from)) {
                if (c.id) out.push(c.id);
                out.push(`${this.secFmt(c.from)} --> ${this.secFmt(c.to)}${c.settings ? ' ' + c.settings : ''}`);
                if (c.text) out.push(...c.text.split('\n'));
                out.push('');
            }
            return out.join('\n');
        },

        mergeSegmentBack(fullCues, segCuesAbs, start, end){
            const kept = fullCues.filter(c => c.to <= start || c.from >= end);
            return [...kept, ...segCuesAbs].sort((a,b)=>a.from-b.from);
        },

        attachSegmentGuards() {
            const v = this.playerEl;
            if (!v) return;

            const clamp = () => {
                if (!this.segmentPlayback.active) return;
                const s = this.trim.start ?? 0;
                const e = (this.trim.end || this.duration || 0);
                if (v.currentTime < s) v.currentTime = s;
                if (v.currentTime > e) v.currentTime = e;
            };

            // при загрузке метаданных — прыгаем на начало сегмента
            v.addEventListener('loadedmetadata', () => {
                if (this.segmentPlayback.active) v.currentTime = this.trim.start || 0;
            });

            // при старте воспроизведения — тоже на начало
            v.addEventListener('play', () => {
                if (!this.segmentPlayback.active) return;
                const s = this.trim.start ?? 0, e = this.trim.end || this.duration || 0;
                if (v.currentTime < s || v.currentTime >= e) v.currentTime = s;
            });

            // не даём уехать за границы при перемотке
            v.addEventListener('seeking', clamp);

            // обрубаем на end (или зацикливаем сегмент)
            v.addEventListener('timeupdate', () => {
                if (!this.segmentPlayback.active) return;
                const s = this.trim.start ?? 0, e = this.trim.end || this.duration || 0;
                if (v.currentTime >= e - 0.02) {
                    if (this.segmentPlayback.loop) {
                        v.currentTime = s;
                        v.play();
                    } else {
                        v.pause();
                        v.currentTime = e;
                    }
                }
            });
        },


        // ---- lifecycle ----
        init() {
            this.playerEl = this.$refs.player;
            this.trackEl  = this.$refs.track;
            this.attachSegmentGuards();

            // duration
            this.playerEl?.addEventListener('loadedmetadata', () => {
                this.duration = this.playerEl.duration || 0;
                if (!this.trim.end || this.trim.end === 0) this.trim.end = this.duration;
                this.renderTextareaForSegment();
                this.previewVtt();
            });
            this.duration = this.playerEl?.duration || 0;
            if (this.duration && (!this.trim.end || this.trim.end===0)) this.trim.end = this.duration;

            // ::cue стили
            this.applyCueStyle();

            // первичная отрисовка
            this.renderTextareaForSegment();
            this.previewVtt();

            // авто-сохранение стиля
            this.$watch('style', () => { if (this.status !== this.STATUS.PROC) this.saveStyle(); }, { deep: true });

            // если когда-то включишь «редактировать весь файл»
            this.$watch('showSegmentOnly', () => this.renderTextareaForSegment());
        },
    }));
});
