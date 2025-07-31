<x-app-layout>
    <h2 class="text-2xl font-semibold mb-6">{{ $clip->slug }}</h2>

    <!-- ================= Video + VTT editor ================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- ► video preview  -->
        <div>
            <video
                x-ref="player"
                class="w-full rounded-lg shadow-lg ring-1 ring-gray-200"
                controls>
                <source src="{{ Storage::url($clip->video_path) }}" type="video/mp4" />
                <track
                    id="subTrack"
                    x-ref="track"
                    label="VTT"
                    kind="subtitles"
                    srclang="en"
                    src="{{ Storage::url($clip->vtt_path) }}"
                    default
                />
            </video>
        </div>

        <!-- ► VTT textarea editor -->
        <div
            x-data="vttEditor({{ json_encode(route('clips.vtt', $clip)) }}, @js($subs))"
            class="flex flex-col min-h-[340px]">

            <h3 class="text-lg font-medium mb-2 text-center">Субтитри (VTT)</h3>

            <textarea
                x-model="text"
                @input="scheduleSave"
                spellcheck="false"
                class="flex-grow resize-y min-h-[300px] rounded-lg border border-gray-300 shadow-sm px-4 py-3 font-mono text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>

            <div class="h-5 mt-1 text-sm">
                <span x-show="saving" class="text-gray-500">Зберігаю…</span>
                <span x-show="saved"  class="text-green-600">✓ збережено</span>
            </div>
        </div>
    </div>

    <!-- ================= Buttons ================= -->
    @php
        $generateUrl = route('clips.hardsubs', $clip);
        $downloadUrl = route('clips.download',  $clip);
        $statusUrl   = route('api.clips.status', $clip);
    @endphp

    <div
        x-data="hardSub({{ $clip->id }}, '{{ $generateUrl }}', '{{ $downloadUrl }}', '{{ $statusUrl }}', '{{ $clip->status->value }}', '{{ csrf_token() }}')"
        x-init="init()"
        class="mt-8 flex justify-center">

        <!-- queued / ready -->
        <template x-if="status === STATUS.QUEUED || status === STATUS.READY">
            <button @click="generate" class="btn-primary">🎞️ Generate video Hard‑sub</button>
        </template>

        <!-- processing -->
        <template x-if="status === STATUS.PROC">
            <button class="btn-secondary" disabled>⏳ Generating…</button>
        </template>

        <!-- done -->
        <template x-if="status === STATUS.DONE">
            <a :href="downloadUrl" class="btn-success" download>📥 Download MP4 with Hard‑sub</a>
        </template>

    </div>

    <!-- ================= Alpine stores ================= -->
    <script>
        const trackEl = document.getElementById('subTrack');

        const STATUS = {
            QUEUED: '{{ App\Enums\ClipStatus::QUEUED->value }}',
            READY:  '{{ App\Enums\ClipStatus::READY->value }}',
            PROC:   '{{ App\Enums\ClipStatus::HARD_PROCESSING->value }}',
            DONE:   '{{ App\Enums\ClipStatus::HARD_DONE->value }}',
        };

        /* ------  VTT Editor  ------ */
        function vttEditor(url, initialText) {
            return {
                text:   initialText,
                saving: false,
                saved:  false,
                timer:  null,

                /* debounce 800 ms */
                scheduleSave() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.save(), 800);
                    this.preview();
                },

                async save() {
                    this.saving = true; this.saved = false;
                    await fetch(url, {
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

                /* live‑preview у <track> через Blob URL */
                preview() {
                    const track = this.$root.closest('[x-app-layout]').querySelector('[x-ref="track"]');
                    if (!track) return;
                    const blob = new Blob([this.text], { type: 'text/vtt' });
                    track.src = URL.createObjectURL(blob);
                }
            }
        }

        /* ------  Hard‑sub control  ------ */
        function hardSub(id, generateUrl, downloadUrl, statusUrl, initialStatus, csrf) {
            return {
                status: initialStatus,
                downloadUrl: downloadUrl,
                poller: null,

                init() {
                    window.addEventListener('vtt-updated', () => {
                        this.status = STATUS.READY;
                        this.downloadUrl = null;
                    });
                    if (this.status === STATUS.PROC) this.startPolling();
                },

                async generate() {
                    this.status = STATUS.PROC;
                    this.startPolling();
                    await fetch(generateUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
                },

                startPolling() {
                    if (this.poller) return;
                    this.poller = setInterval(async () => {
                        const res = await fetch(statusUrl).then(r => r.json());
                        this.status = res.status;
                        this.downloadUrl = res.url;
                        if (this.status === STATUS.DONE) clearInterval(this.poller);
                    }, 5000);
                }
            }
        }
    </script>

    <!-- ===== Tailwind‑style button shortcuts ===== -->
    <style>
        .btn-primary  { @apply px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60; }
        .btn-secondary{ @apply px-5 py-2 rounded-lg bg-gray-400  text-white cursor-not-allowed; }
        .btn-success  { @apply px-5 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700; }
    </style>

</x-app-layout>
