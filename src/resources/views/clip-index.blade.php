<x-app-layout>
    <h1 class="text-2xl mb-4">Готові кліпи</h1>

    @if($clips->isEmpty())
        <p class="text-gray-500">Тут поки що порожньо. Зайди на сторінку «Додати новий» і закинь перший кліп 😉</p>
    @else
        <ul class="space-y-2">
            @foreach ($clips as $clip)
                <li class="border p-3 rounded flex justify-between items-center">
                    <input type="text" name="title" value="{{ $clip->name_video }}">
                    <a href="{{ route('clips.show', $clip) }}"
                       class="text-blue-500 hover:underline">Переглянути →</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-app-layout>
