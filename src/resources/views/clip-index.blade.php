<x-app-layout>
    <h1 class="text-2xl mb-4">Готові кліпи</h1>

    @if($clips->isEmpty())
        <p class="text-gray-500">Тут поки що порожньо. Зайди на сторінку «Додати новий» і закинь перший кліп 😉</p>
    @else
        <ul class="space-y-2"
            x-data="clipsList()"
            @open-delete.window="open($event.detail)"
            x-cloak>

            @foreach ($clips as $clip)
                <li class="border p-3 rounded flex justify-between items-center"
                    data-clip-id="{{ $clip->id }}">
                    <div class="flex items-center gap-2">
                        <span>{{ $clip->name_video }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('clips.show', $clip) }}" class="text-blue-500 hover:underline">
                            Переглянути →
                        </a>
                        <button type="button"
                                @click="$dispatch('open-delete', { id: {{ $clip->id }}, title: @js($clip->name_video) })"
                                class="text-red-600">
                            🗑️ Видалити
                        </button>
                    </div>
                </li>
            @endforeach

            {{-- Модалка подтверждения --}}
            <template x-if="showConfirm">
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                     x-transition.opacity
                     @keydown.escape.window="close()"
                     @click.self="close()">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-80" x-transition.scale>
                        <h2 class="text-lg font-semibold mb-2">Підтвердити видалення</h2>
                        <p class="mb-6 text-gray-600">
                            Ви дійсно хочете видалити
                            <span class="font-semibold" x-text="selectedTitle"></span>?
                        </p>
                        <div class="flex justify-end gap-3">
                            <button @click="close()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                                Скасувати
                            </button>
                            <button
                                @click="confirmRemove()"
                                :disabled="removingIds.has(selectedId)"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 min-w-[110px] grid place-items-center"
                            >
                                <span x-show="!removingIds.has(selectedId)">Видалити</span>
                                <span x-show="removingIds.has(selectedId)" class="inline-flex items-center gap-2">Wait
                                <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_OSmW{transform-origin:center;animation:spinner_T6mA .75s step-end infinite}@keyframes spinner_T6mA{8.3%{transform:rotate(30deg)}16.6%{transform:rotate(60deg)}25%{transform:rotate(90deg)}33.3%{transform:rotate(120deg)}41.6%{transform:rotate(150deg)}50%{transform:rotate(180deg)}58.3%{transform:rotate(210deg)}66.6%{transform:rotate(240deg)}75%{transform:rotate(270deg)}83.3%{transform:rotate(300deg)}91.6%{transform:rotate(330deg)}100%{transform:rotate(360deg)}}</style><g class="spinner_OSmW"><rect x="11" y="1" width="2" height="5" opacity=".14"/><rect x="11" y="1" width="2" height="5" transform="rotate(30 12 12)" opacity=".29"/><rect x="11" y="1" width="2" height="5" transform="rotate(60 12 12)" opacity=".43"/><rect x="11" y="1" width="2" height="5" transform="rotate(90 12 12)" opacity=".57"/><rect x="11" y="1" width="2" height="5" transform="rotate(120 12 12)" opacity=".71"/><rect x="11" y="1" width="2" height="5" transform="rotate(150 12 12)" opacity=".86"/><rect x="11" y="1" width="2" height="5" transform="rotate(180 12 12)"/></g></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </ul>
    @endif
</x-app-layout>

<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    [x-cloak] { display:none !important; }
    /* плавное исчезновение строки */
    .fade-out {
        opacity: 0;
        transform: scale(0.98);
        transition: opacity .3s, transform .3s;
    }
    /* базовый transition на <li> */
    li[data-clip-id] {
        transition: opacity .3s, transform .3s;
    }
</style>
