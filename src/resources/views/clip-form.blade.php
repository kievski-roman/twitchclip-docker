<x-guest-layout>
    <div class="max-w-xl mx-auto p-6 ">
        <x-auth-card>
        <form action="{{ route('clip.get') }}" method="POST" class="justify-center">
            @csrf
            <label>Введи нік стрімера:</label>
            <input type="text" name="username" class=" w-full rounded-bl-md rounded-tl-md bg-gray-100 px-4 py-2.5 text-gray-700 focus:outline-blue-500 my-3" required>
            <button class="inline-flex items-center px-4 py-2 bg-gray-800 border
            border-transparent rounded-md font-semibold text-xs
             text-white uppercase tracking-widest hover:bg-gray-700
             focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2
              focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150  "
                    type="submit">Get video</button>
        </form>
        </x-auth-card>
        @if ($errors->any())
            <p class="text-red-600 mt-4">{{ $errors->first() }}</p>
        @endif
    </div>
</x-guest-layout>

