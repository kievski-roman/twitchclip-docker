<x-guest-layout>
    <h1>🎬 Кліпи стрімера: {{ $username }}</h1>

    {{-- 1) Пробрасываем данные с бэка в валидный JS (никаких JSON.parse в атрибуте) --}}
    <script>
        window.PAGE = {
            clips: @js($clips ?? []),
            cursor: @js($cursor ?? null),           // важливо: null, не 'null'
            count:  @js($count  ?? 5),              // дефолт
            username: @js($username),
            apiUrl: @js(route('api.clips.index', ['username' => $username])),
        };
    </script>

    {{-- 2) Небольшой стиль, чтобы не моргал контент до инициализации Alpine --}}
    <style>[x-cloak]{ display:none !important; }</style>

    {{-- 3) Разметка компонента (сам атрибут x-data теперь короткий) --}}
    <div x-data="clipsPage()" x-cloak>
        <template x-for="clip in clips" :key="clip.url">
            <div style="border:1px solid #ddd; padding:15px; margin-bottom:30px;">
                <h3 x-text="clip.title"></h3>
                <a :href="clip.url" target="_blank">🔗 Перейти на Twitch</a><br><br>
                <img :src="clip.thumbnail_url" width="300" loading="lazy"><br>

                <form action="{{ route('clip.download') }}" method="POST" style="margin-top:10px;">
                    @csrf
                    <input type="hidden" name="url" :value="clip.url">
                    <input type="hidden" name="title" :value="clip.title">
                    <button class="inline-flex items-center px-4 py-2 bg-gray-800 border
            border-transparent rounded-md font-semibold text-xs
             text-white uppercase tracking-widest hover:bg-gray-700
             focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2
              focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150  " type="submit">⬇️ Завантажити відео</button>
                </form>
            </div>
        </template>

        <button class="m-4  inline-flex items-center px-4 py-2 bg-gray-800 border
            border-transparent rounded-md font-semibold text-xs
             text-white uppercase tracking-widest hover:bg-gray-700
             focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2
              focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150  " x-show="cursor" @click="loadMore()">Ещё</button>
    </div>

    {{-- 4) Инициализация Alpine-компонента --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('clipsPage', () => ({
                // Стартовое состояние из window.PAGE
                clips:    Array.isArray(window.PAGE?.clips) ? window.PAGE.clips : [],
                cursor:   window.PAGE?.cursor ?? null,
                count:    Number.isFinite(window.PAGE?.count) ? window.PAGE.count : 5,
                username: window.PAGE?.username ?? '',
                apiUrl:   window.PAGE?.apiUrl ?? '',

                // Метод подгрузки
                async loadMore() {
                    if (!this.cursor) return;

                    try {
                        const url = `${this.apiUrl}?after=${encodeURIComponent(this.cursor)}&count=${this.count}`;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

                        if (!res.ok) throw new Error(`HTTP ${res.status}`);

                        const json = await res.json();
                        const data = Array.isArray(json?.data) ? json.data : [];
                        const next = (json?.cursor ?? null); // важно: реальный null, не строка

                        if (data.length) this.clips.push(...data);
                        this.cursor = next; // если null — кнопка "Ещё" скроется по x-show="cursor"
                    } catch (e) {
                        console.error('loadMore error', e);
                    }
                },
            }));
        });
    </script>
</x-guest-layout>
