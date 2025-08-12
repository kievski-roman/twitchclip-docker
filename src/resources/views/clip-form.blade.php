<x-guest-layout>
    <div class="max-w-xl mx-auto p-6 ">
        <x-auth-card>
        <form action="{{ route('clip.get') }}" method="POST" class="justify-center">
            @csrf
            <label>Введи нік стрімера:</label>
            <input type="text" name="username" class=" w-full rounded-bl-md rounded-tl-md bg-gray-100 px-4 py-2.5 text-gray-700 focus:outline-blue-500 my-3" required>
            <button
                @click.prevent="run"
                x-data="btnLoader(() => $el.closest('form').submit())"
            :disabled="loading"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border
            border-transparent rounded-md font-semibold text-xs
             text-white uppercase tracking-widest hover:bg-gray-700
             focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2
              focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150  "
                    type="submit">
                <span x-show="!loading">Get video</span>
                <span x-show="loading" class="inline-flex items-center gap-2">
                  Wait
 <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_OSmW{transform-origin:center;animation:spinner_T6mA .75s step-end infinite}@keyframes spinner_T6mA{8.3%{transform:rotate(30deg)}16.6%{transform:rotate(60deg)}25%{transform:rotate(90deg)}33.3%{transform:rotate(120deg)}41.6%{transform:rotate(150deg)}50%{transform:rotate(180deg)}58.3%{transform:rotate(210deg)}66.6%{transform:rotate(240deg)}75%{transform:rotate(270deg)}83.3%{transform:rotate(300deg)}91.6%{transform:rotate(330deg)}100%{transform:rotate(360deg)}}</style><g class="spinner_OSmW"><rect x="11" y="1" width="2" height="5" opacity=".14"/><rect x="11" y="1" width="2" height="5" transform="rotate(30 12 12)" opacity=".29"/><rect x="11" y="1" width="2" height="5" transform="rotate(60 12 12)" opacity=".43"/><rect x="11" y="1" width="2" height="5" transform="rotate(90 12 12)" opacity=".57"/><rect x="11" y="1" width="2" height="5" transform="rotate(120 12 12)" opacity=".71"/><rect x="11" y="1" width="2" height="5" transform="rotate(150 12 12)" opacity=".86"/><rect x="11" y="1" width="2" height="5" transform="rotate(180 12 12)"/></g></svg>
                </span>
            </button>
        </form>
        </x-auth-card>
        @if ($errors->any())
            <p class="text-red-600 mt-4">{{ $errors->first() }}</p>
        @endif
    </div>
</x-guest-layout>

