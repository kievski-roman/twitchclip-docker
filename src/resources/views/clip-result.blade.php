<x-guest-layout>
 <h1>🎬 Кліпи стрімера: {{ $username }}</h1>

    @foreach($clips as $clip)
        <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 30px;">
            <h3>{{ $clip['title'] }}</h3>-
            <a href="{{ $clip['url'] }}" target="_blank">🔗 Перейти на Twitch</a><br><br>
            <img src="{{ $clip['thumbnail_url'] }}" width="300"><br>
            <form action="{{ route('clip.download') }}" method="POST" style="margin-top: 10px;">
                @csrf
                <input type="hidden" name="url" value="{{ $clip['url'] }}">
                <input type="hidden" name="title" value="{{ $clip['title'] }}">
                <button type="submit">⬇️ Завантажити відео</button>
            </form>
            @if ($errors->any())
                <div style="color:red">
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('flash'))
                <div style="color:green">{{ session('flash') }}</div>
            @endif

        </div>
    @endforeach
</x-guest-layout>


