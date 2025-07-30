<nav class="bg-white dark:bg-zinc-900 border-b border-gray-100" x-data="{ open:false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
        {{-- Logo --}}
        <a href="{{ route('clip.form') }}" class="text-lg font-semibold">
            MyClip
        </a>

        {{-- ===== Desktop menu ===== --}}
        <div class="hidden sm:flex items-center space-x-6">
            @guest
                <a href="{{ route('clip.form') }}"  class="hover:underline">Search</a>
                <a href="{{ route('login') }}"     class="hover:underline">Log&nbsp;in</a>
                <a href="{{ route('register') }}"  class="hover:underline">Register</a>
            @else
                <a href="{{ route('dashboard') }}"
                   class="hover:underline {{ request()->routeIs('dashboard') ? 'font-semibold' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('clip.form') }}"
                   class="hover:underline {{ request()->routeIs('clip.form') ? 'font-semibold' : '' }}">
                    Search
                </a>
                <a href="{{ route('clips.index') }}"
                   class="hover:underline {{ request()->routeIs('clips.*') ? 'font-semibold' : '' }}">
                    My&nbsp;clips
                </a>

                {{-- User dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center hover:underline">
                            {{ Auth::user()->name }}
                            <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">


                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @endguest

            {{-- Theme switch --}}
            <button @click="$store.theme.toggle()" class="text-sm border px-2 py-1 rounded">
                Toggle
            </button>
        </div>

        {{-- ===== Burger btn (mobile) ===== --}}
        <button @click="open = !open"
                class="sm:hidden inline-flex items-center justify-center p-2 rounded-md hover:bg-gray-200 dark:hover:bg-zinc-700">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="open" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- ===== Mobile menu ===== --}}
    <div class="sm:hidden" x-show="open" @click.away="open = false">
        <div class="px-4 pt-4 pb-6 space-y-2">
            @guest
                <a href="{{ route('clip.form') }}"  class="block px-3 py-2 hover:underline">Search</a>
                <a href="{{ route('login') }}"     class="block px-3 py-2 hover:underline">Login</a>
                <a href="{{ route('register') }}"  class="block px-3 py-2 hover:underline">Register</a>
            @else
                <a href="{{ route('dashboard') }}"  class="block px-3 py-2 hover:underline">Dashboard</a>
                <a href="{{ route('clip.form') }}"  class="block px-3 py-2 hover:underline">Search</a>
                <a href="{{ route('clips.index') }}"class="block px-3 py-2 hover:underline">My clips</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-left px-3 py-2 hover:underline">Log Out</button>
                </form>
            @endguest

            <button @click="$store.theme.toggle()" class="w-full text-left px-3 py-2 border rounded">
                Toggle Theme
            </button>
        </div>
    </div>
</nav>
