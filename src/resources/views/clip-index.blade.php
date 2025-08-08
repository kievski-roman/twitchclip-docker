<x-app-layout>
    <h1 class="text-2xl mb-4">Готові кліпи</h1>

    @if($clips->isEmpty())
        <p class="text-gray-500">Тут поки що порожньо. Зайди на сторінку «Додати новий» і закинь перший кліп 😉</p>
    @else
        <ul class="space-y-2">
            @foreach ($clips as $clip)
                <li class="border p-3 rounded flex justify-between items-center"
                    x-data="clipTitleEditor({{ $clip->id }}, @js($clip->name_video))">

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

                    <a href="{{ route('clips.show', $clip) }}"
                       class="text-blue-500 hover:underline">Переглянути →</a>
                </li>
                <li class="border p-3 rounded flex justify-between items-center"
                    x-data="clipRow({{ $clip->id }})">
                    <span>{{ $clip->name_video }}</span>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="remove()" class="text-red-600">🗑️ Видалити</button>
                    </div>
                </li>
            @endforeach


        </ul>
    @endif
</x-app-layout>

{{-- Метка CSRF для fetch --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

