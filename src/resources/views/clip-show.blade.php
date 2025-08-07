<x-app-layout>

    <h2 class="text-2xl font-semibold mb-6">{{ $clip->slug }}</h2>

    <!-- Видео + VTT-редактор -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <video
                x-ref="player"
                class="w-full rounded shadow"
                controls
            >
                <source src="{{ Storage::url($clip->video_path) }}" type="video/mp4" />
                <track
                    x-ref="track"
                    label="Subtitles"
                    kind="subtitles"
                    srclang="en"
                    src="{{ Storage::url($clip->vtt_path) }}"
                    default
                />
            </video>
        </div>
        <div x-data="vttEditor(
                {{ json_encode(route('clips.vtt', $clip)) }},
                @js($subs)
            )"
             class="flex flex-col"
        >
            <h3 class="text-lg mb-2 text-center">Субтитри (VTT)</h3>
            <textarea
                x-model="text"
                @input="scheduleSave"
                spellcheck="false"
                class="flex-grow resize-y min-h-[300px] border p-3 font-mono text-sm dark:text-black"
            ></textarea>
            <div class="h-5 mt-1 text-sm">
                <span x-show="saving" class="text-gray-500">Зберігаю…</span>
                <span x-show="saved"  class="text-green-600">✓ збережено</span>
            </div>
        </div>
    </div>

    <!-- Контролы стиля и кнопки -->
    <div
        x-data="hardSub(
        {{ $clip->id }},
        '{{ $generateUrl }}',
        '{{ $downloadUrl }}',
        '{{ $statusUrl }}',
        '{{ $styleUrl }}',
        '{{ csrf_token() }}',
        {{ json_encode($clip->vtt_style ?? [
            'color'=>'#ffff00','fontSize'=>24,
            'outline'=>'#000000','fontStyle'=>'normal',
            'ratio' => '16:9',
        ]) }}
      )"
        x-init="init()"
        class="mt-8 flex gap-4"
    >
        <label>
            Цвет:
            <input type="color" x-model="style.color" />
        </label>

        <label>
            Размер:
            <input class="dark:text-black" type="number" x-model="style.fontSize" min="10" max="72" />px
        </label>

        <label>
            Обводка:
            <input type="color" x-model="style.outline" />
        </label>

        <label>
            Шрифт:
            <select class="dark:text-black" x-model="style.fontStyle">
                <option class="dark:text-black" value="normal">Normal</option>
                <option class="dark:text-black" value="bold">Bold</option>
                <option class="dark:text-black" value="italic">Italic</option>
                <option value="bolditalic">Bold + Italic</option>
            </select>
        </label>
        <label>
            Ratio:
            <select class="dark:text-black"  x-model="style.ratio">
                <option class="dark:text-black"  value="16:9">16:9</option>
                <option class="dark:text-black"  value="9:16">9:16</option>
            </select>
        </label>

        <!-- Кнопки -->
        <template x-if="status===STATUS.QUEUED||status===STATUS.READY">
            <button @click="generate" class="btn-primary">Generate</button>
        </template>
        <template x-if="status===STATUS.PROC">
            <button class="btn-secondary" disabled>Generating…</button>
        </template>
        <template x-if="status===STATUS.DONE">
            <a :href="downloadUrl" class="btn-success" download>Download</a>
        </template>
    </div>
    <!-- Alpine.js logic -->
    <script>
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
                ratio: '16:9',
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

                    /// maybe in future make show ratio for user
                    //this.$watch('style.ratio',  () => this.applyCueStyle());

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
                        body: JSON.stringify({ style: this.style, ratio: this.style.ratio  })
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
    </script>

    <style>
        .btn-primary   { @apply bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded; }
        .btn-secondary { @apply bg-gray-400 text-white px-4 py-2 rounded opacity-50 cursor-not-allowed; }
        .btn-success   { @apply bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded; }
    </style>
</x-app-layout>
