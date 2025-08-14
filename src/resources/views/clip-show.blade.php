<x-app-layout>
    @php
        $STATUS = [
          'QUEUED' => \App\Enums\ClipStatus::QUEUED->value,
          'READY'  => \App\Enums\ClipStatus::READY->value,
          'PROC'   => \App\Enums\ClipStatus::HARD_PROCESSING->value,
          'DONE'   => \App\Enums\ClipStatus::HARD_DONE->value,
        ];
    @endphp

    <style>
        /* скрыть элементы с x-cloak до инициализации Alpine */
        [x-cloak]{ display:none !important; }

        /* Кастом трека/ползунков (Tailwind не умеет) */
        .range-thumb::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 18px; height: 18px;
            background: #2563eb; border-radius: 9999px; border: 2px solid #fff;
            box-shadow: 0 0 0 1px #2563eb; cursor: pointer; position: relative; z-index: 3;
        }
        .range-thumb::-moz-range-thumb {
            width: 18px; height: 18px; background: #2563eb; border: 2px solid #fff; border-radius: 9999px; cursor: pointer;
        }
        .range-thumb { pointer-events: none; }
        .range-thumb::-webkit-slider-thumb { pointer-events: auto; }
        .range-thumb::-moz-range-thumb    { pointer-events: auto; }
        .range-thumb::-webkit-slider-runnable-track { height: 40px; background: transparent; }
        .range-thumb::-moz-range-track { height: 40px; background: transparent; }
    </style>

    <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-6">

        {{-- Заголовок / редактор названия + кнопка истории --}}
        <li class="border rounded bg-white dark:bg-slate-800 shadow-sm p-3 flex flex-col sm:flex-row gap-3 sm:gap-4 sm:items-center sm:justify-between">
            <div x-data="clipTitleEditor({{ $clip->id }}, @js($clip->name_video), '{{ csrf_token() }}')" class="flex-1">
                <template x-if="!editing">
                    <div class="flex items-center gap-2">
                        <span class="font-medium break-words" x-text="tempTitle"></span>
                        <button @click="editing = true" class="text-sm text-gray-500 hover:text-gray-700">✏️</button>
                    </div>
                </template>
                <template x-if="editing">
                    <div class="flex items-center gap-2 max-w-full">
                        <input type="text" x-model="tempTitle" maxlength="255"
                               class="w-full sm:w-96 border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-black" />
                        <button @click="save()" class="px-3 py-2 rounded bg-green-600 hover:bg-green-700 text-white">💾</button>
                        <button @click="editing = false" class="px-3 py-2 rounded bg-red-600 hover:bg-red-700 text-white">✖️</button>
                    </div>
                </template>
            </div>

            @can('download', $clip)
                @if($clip->status === \App\Enums\ClipStatus::HARD_DONE)
                    <a href="{{ route('clips.download', $clip) }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded border border-gray-300 text-gray-800 hover:bg-gray-50">
                        generation history
                    </a>
                @endif
            @endcan
        </li>

        {{-- Главный редактор --}}
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
            class="space-y-6"
        >

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                {{-- ЛЕВАЯ КОЛОНКА: Видео + таймлайн --}}
                <div class="bg-white dark:bg-slate-800 rounded-lg border shadow p-3 sm:p-4">
                    <div class="rounded-lg overflow-hidden border">
                        <video x-ref="player" class="w-full bg-black aspect-video" controls playsinline>
                            <source src="{{ Storage::url($clip->video_path) }}" type="video/mp4" />
                            <track x-ref="track" label="Subtitles" kind="subtitles" srclang="en"
                                   src="{{ Storage::url($clip->vtt_path) }}" default />
                        </video>
                    </div>

                    <div class="mt-4">
                        <div class="text-xs sm:text-sm text-gray-600 mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div>Довжина: <span class="tabular-nums" x-text="duration.toFixed(3)"></span>s</div>
                            <div class="hidden sm:flex items-center gap-4">
                                <div>start: <span class="tabular-nums" x-text="trim.start.toFixed(3)"></span>s</div>
                                <div>end: <span class="tabular-nums" x-text="trim.end.toFixed(3)"></span>s</div>
                            </div>
                        </div>

                        <div class="relative h-10 select-none">
                            <div class="absolute inset-0 rounded-full bg-gray-200"></div>
                            <div class="absolute top-0 bottom-0 rounded-full bg-blue-300" :style="rangeFillStyle"></div>

                            <input type="range" min="0" step="0.01" :max="duration"
                                   x-model.number="trim.start" @input="onStartChange"
                                   class="range-thumb absolute inset-0 w-full appearance-none bg-transparent pointer-events-none">
                            <input type="range" min="0" step="0.01" :max="duration"
                                   x-model.number="trim.end" @input="onEndChange"
                                   class="range-thumb absolute inset-0 w-full appearance-none bg-transparent pointer-events-none">
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button @click="previewTrim()" class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white">Preview</button>
                            <button @click="resetTrim()"   class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800">Reset</button>

                            <div class="sm:hidden text-xs text-gray-600 ml-auto">
                                <span>start: <span x-text="trim.start.toFixed(2)"></span>s</span> ·
                                <span>end: <span x-text="trim.end.toFixed(2)"></span>s</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ПРАВАЯ КОЛОНКА: Редактор VTT --}}
                <div class="bg-white dark:bg-slate-800 rounded-lg border shadow p-3 sm:p-4 flex flex-col">
                    <h3 class="text-base sm:text-lg font-medium mb-2 text-center sm:text-left">Субтитри (VTT)</h3>
                    <textarea
                        x-model="text"
                        @input="onTextareaInput($event)"
                        spellcheck="false"
                        class="w-full min-h-[300px] sm:min-h-[360px] md:min-h-[420px] border rounded-lg p-3 font-mono text-sm dark:text-black focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="00:00:00.000 --> 00:00:02.000&#10;Text…"
                    ></textarea>
                    <div class="h-5 mt-2 text-sm">
                        <span x-show="saving" class="text-gray-500 inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"></path></svg>
                            Зберігаю…
                        </span>
                        <span x-show="saved" class="text-green-600">✓ збережено</span>
                    </div>
                </div>
            </div>

            {{-- Контролы стиля + кнопки/прогресс --}}
            <div class="bg-white dark:bg-slate-800 rounded-lg border shadow p-3 sm:p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <label class="flex flex-col gap-1">
                        <span class="text-sm text-gray-600">Цвет</span>
                        <input type="color" x-model="style.color" class="h-10 w-16 border rounded" />
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-sm text-gray-600">Размер</span>
                        <div class="flex items-center gap-2">
                            <input class="border rounded px-3 py-2 w-24 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-black"
                                   type="number" min="10" max="72" x-model.number="style.fontSize" />
                            <span class="text-gray-500">px</span>
                        </div>
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-sm text-gray-600">Обводка</span>
                        <input type="color" x-model="style.outline" class="h-10 w-16 border rounded" />
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-sm text-gray-600">Шрифт</span>
                        <select x-model="style.fontStyle" class="border rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-black">
                            <option value="normal">Normal</option>
                            <option value="bold">Bold</option>
                            <option value="italic">Italic</option>
                            <option value="bolditalic">Bold + Italic</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-1">
                        <span class="text-sm text-gray-600">Ratio</span>
                        <select x-model="style.ratio" class="border rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-black">
                            <option value="9:16">9:16</option>
                            <option value="16:9">16:9</option>
                        </select>
                    </label>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-center">
                    <template x-if="status===STATUS.QUEUED||status===STATUS.READY">
                        <button @click="generate"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white">
                            <span>Generate</span>
                        </button>
                    </template>

                    <template x-if="status===STATUS.PROC">
                        <div class="w-full sm:w-auto flex-1">
                            <div class="btn-secondary inline-flex items-center gap-2 w-full justify-center" disabled>
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
                                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"></path>
                                </svg>
                                Generating…
                            </div>
                            <div class="mt-2 text-xs text-gray-500 text-center">
                                Це може зайняти кілька хвилин для довгих відео.
                            </div>
                        </div>
                    </template>



                    <template x-if="status===STATUS.DONE">
                        <a :href="downloadUrl" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 rounded bg-green-600 hover:bg-green-700 text-white" download>
                            Download
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</x-app-layout>
