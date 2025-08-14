<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 py-6">
        <h1 class="text-2xl md:text-3xl font-semibold mb-6">
            🎬 Кліпи стрімера: {{ $username }}
        </h1>

        {{-- 1) Пробрасываем данные с бэка в валидный JS --}}
        <script>
            window.PAGE = {
                clips:    @js($clips ?? []),
                cursor:   @js($cursor ?? null),
                count:    @js($count  ?? 6),
                username: @js($username),
                apiUrl:   @js(route('api.clips.index', ['username' => $username])),
            };
        </script>

        <style>[x-cloak]{display:none!important}</style>

        {{-- 2) Компонент страницы --}}
        <div x-data="clipsPage()" x-cloak>
            {{-- Пусто --}}
            <template x-if="clips.length === 0">
                <p class="text-gray-500">Нічого не знайшлось. Спробуй інший нік 🙂</p>
            </template>

            {{-- Сетка карточек --}}
            <div class="grid sm:grid-cols-2 gap-4" x-show="clips.length">
                <template x-for="clip in clips" :key="clip.url">
                    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                        <h3 class="font-medium leading-snug mb-2 break-words" x-text="clip.title"></h3>

                        <a :href="clip.url" target="_blank"
                           class="text-sm text-indigo-600 hover:text-indigo-700 mb-3">🔗 Відкрити на Twitch</a>

                        <img :src="clip.thumbnail_url" alt=""
                             class="rounded-lg border aspect-video object-cover w-full mb-4" loading="lazy">
                       {{-- Кнопка загрузки — нативный сабмит + лоудер --}}
                        <form action="{{ route('clip.download') }}" method="POST"
                              x-data="{ loading:false }"
                              @submit="loading = true"
                              class="mt-auto">
                            @csrf
                            <input type="hidden" name="url"   :value="clip.url">
                            <input type="hidden" name="title" :value="clip.title">


                            <button type="submit"
                                    :disabled="loading"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md
                             bg-gray-800 text-white font-semibold text-sm tracking-wide
                             hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span x-show="!loading">⬇️ Завантажити відео</span>
                                <span x-show="loading" class="inline-flex items-center gap-2">
                  Зачекай
                  <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            fill="none" opacity=".25"></circle>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"></path>
                  </svg>
                </span>
                            </button>
                        </form>
                    </div>
                </template>
            </div>

            {{-- Кнопка "Ещё" --}}
            <div class="flex justify-center">
                <button
                    x-show="cursor"
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="mt-6 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-md
                 bg-gray-800 text-white font-semibold text-sm tracking-wide
                 hover:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!loadingMore">Ещё</span>
                    <span x-show="loadingMore" class="inline-flex items-center gap-2">
            Завантаження…
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                      fill="none" opacity=".25"></circle>
              <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"></path>
            </svg>
          </span>
                </button>
            </div>
        </div>
    </div>

    {{-- 3) Логика Alpine --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('clipsPage', () => ({
                // state
                clips:    Array.isArray(window.PAGE?.clips) ? window.PAGE.clips : [],
                cursor:   window.PAGE?.cursor ?? null,
                count:    Number.isFinite(window.PAGE?.count) ? window.PAGE.count : 5,
                apiUrl:   window.PAGE?.apiUrl ?? '',
                loadingMore: false,

                async loadMore() {
                    if (!this.cursor || this.loadingMore) return;
                    this.loadingMore = true;
                    try {
                        const url = `${this.apiUrl}?after=${encodeURIComponent(this.cursor)}&count=${this.count}`;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);

                        const json  = await res.json();
                        const data  = Array.isArray(json?.data) ? json.data : [];
                        const next  = json?.cursor ?? null;

                        if (data.length) this.clips.push(...data);
                        this.cursor = next; // если null — кнопка скрывается
                    } catch (e) {
                        console.error('loadMore error', e);
                        alert('Не вдалося завантажити ще кліпи.');
                    } finally {
                        this.loadingMore = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
