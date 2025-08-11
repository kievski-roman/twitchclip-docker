<x-app-layout>
    @php
        $STATUS = [
          'QUEUED' => \App\Enums\ClipStatus::QUEUED->value,
          'READY'  => \App\Enums\ClipStatus::READY->value,
          'PROC'   => \App\Enums\ClipStatus::HARD_PROCESSING->value,
          'DONE'   => \App\Enums\ClipStatus::HARD_DONE->value,
        ];
    @endphp

    <h2 class="text-2xl font-semibold mb-6">{{ $clip->name_video }}</h2>

    <li class="border p-3 rounded flex justify-between items-center"
        x-data="clipTitleEditor({{ $clip->id }}, @js($clip->name_video), '{{ csrf_token() }}')">
        <template x-if="!editing">
            <div class="flex items-center gap-2">
                <span x-text="tempTitle"></span>
                <button @click="editing = true" class="text-sm text-gray-500">✏️</button>
            </div>
        </template>
        <template x-if="editing">
            <div class="flex items-center gap-2">
                <input type="text" x-model="tempTitle" maxlength="255" class="border px-2 py-1">
                <button @click="save()" class="text-sm text-green-600">💾</button>
                <button @click="editing = false" class="text-sm text-red-600">✖️</button>
            </div>
        </template>
    </li>

    <div
        x-data="clipEditor({
          csrf: '{{ csrf_token() }}',
          STATUS: { QUEUED:'{{ $STATUS['QUEUED'] }}', READY:'{{ $STATUS['READY'] }}', PROC:'{{ $STATUS['PROC'] }}', DONE:'{{ $STATUS['DONE'] }}' },
          urls: {
            saveVtt:    @js(route('clips.vtt', $clip)),
            saveStyle:  @js($styleUrl),
            gen:        @js($generateUrl),
            status:     @js($statusUrl),
            download:   @js($downloadUrl),
            updateTitle:@js(route('clips.updateTitle', $clip)),
          },
          initial: {
            title: @js($clip->name_video),
            vttText: @js($subs),
            style: @js($clip->vtt_style ?? ['color'=>'#ffff00','fontSize'=>24,'outline'=>'#000000','fontStyle'=>'normal','ratio'=>'16:9']),
          }
        })"
        x-init="init()"
    >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <video x-ref="player" class="w-full rounded shadow" controls>
                    <source src="{{ Storage::url($clip->video_path) }}" type="video/mp4" />
                    <track x-ref="track" label="Subtitles" kind="subtitles" srclang="en"
                           src="{{ Storage::url($clip->vtt_path) }}" default />
                </video>

                <div class="mt-5">
                    <div class="text-sm text-gray-600 mb-2 flex items-center justify-between">
                        <div>Довжина: <span x-text="duration.toFixed(3)"></span>s</div>
                        <div class="flex items-center gap-4">
                            <div>start: <span x-text="trim.start.toFixed(3)"></span>s</div>
                            <div>end: <span x-text="trim.end.toFixed(3)"></span>s</div>
                        </div>
                    </div>

                    <div class="relative h-8">
                        <div class="absolute inset-0 rounded-full bg-gray-200"></div>
                        <div class="absolute top-0 bottom-0 rounded-full bg-blue-300"
                             :style="rangeFillStyle"></div>

                        <input type="range" min="0" step="0.01"
                               :max="duration"
                               x-model.number="trim.start"
                               @input="onStartChange"
                               class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none range-thumb">
                        <input type="range" min="0" step="0.01"
                               :max="duration"
                               x-model.number="trim.end"
                               @input="onEndChange"
                               class="absolute inset-0 w-full appearance-none bg-transparent pointer-events-none range-thumb">
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button @click="previewTrim()" class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">Preview</button>
                        <button @click="resetTrim()"   class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">Reset</button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col">
                <h3 class="text-lg mb-2 text-center">Субтитри (VTT)</h3>
                <textarea
                    x-model="text"
                    @input="onTextareaInput($event)"
                    spellcheck="false"
                    class="flex-grow resize-y min-h-[300px] border p-3 font-mono text-sm dark:text-black"
                ></textarea>
                <div class="h-5 mt-1 text-sm">
                    <span x-show="saving" class="text-gray-500">Зберігаю…</span>
                    <span x-show="saved"  class="text-green-600">✓ збережено</span>
                </div>
            </div>
        </div>

        <div class="mt-8 flex gap-4">
            <label>
                Цвет:
                <input type="color" x-model="style.color" />
            </label>

            <label>
                Размер:
                <input class="dark:text-black" type="number" x-model.number="style.fontSize" min="10" max="72" />px
            </label>

            <label>
                Обводка:
                <input type="color" x-model="style.outline" />
            </label>

            <label>
                Шрифт:
                <select x-model="style.fontStyle">
                    <option value="normal">Normal</option>
                    <option value="bold">Bold</option>
                    <option value="italic">Italic</option>
                    <option value="bolditalic">Bold + Italic</option>
                </select>
            </label>
            <label>
                Ratio:
                <select x-model="style.ratio">
                    <option value="16:9">16:9</option>
                    <option value="9:16">9:16</option>
                </select>
            </label>

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
    </div>

    <style>
        .btn-primary   { @apply bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded; }
        .btn-secondary { @apply bg-gray-400 text-white px-4 py-2 rounded opacity-50 cursor-not-allowed; }
        .btn-success   { @apply bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded; }

        .range-thumb::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 14px; height: 14px;
            background: #2563eb; border-radius: 9999px; border: 2px solid white;
            box-shadow: 0 0 0 1px #2563eb;
            cursor: pointer;
            position: relative; z-index: 3;
        }
        .range-thumb::-moz-range-thumb {
            width: 14px; height: 14px; background: #2563eb; border: 2px solid white; border-radius: 9999px; cursor: pointer;
        }
        .range-thumb { pointer-events: none; }
        .range-thumb::-webkit-slider-thumb { pointer-events: auto; }
        .range-thumb::-moz-range-thumb    { pointer-events: auto; }
        .range-thumb::-webkit-slider-runnable-track { height: 32px; background: transparent; }
        .range-thumb::-moz-range-track { height: 32px; background: transparent; }
    </style>
</x-app-layout>

<meta name="csrf-token" content="{{ csrf_token() }}">
