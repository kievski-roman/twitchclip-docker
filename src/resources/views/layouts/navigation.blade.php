



{{--  resources/views/layouts/navigation.blade.php  --}}
<nav class="bg-white border-b border-gray-100" x-data="{ open:false }">
    {{-- ================= Гість (не залогінений) ================= --}}
    @guest
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            {{-- Лого --}}
            <a href="{{ route('clip.form') }}" class="text-lg font-semibold">MyClip</a>

            {{-- Посилання --}}
            <div class="space-x-6">
                <a href="{{ route('clip.form') }}" class="text-gray-700 hover:text-gray-900">
                    {{ __('Search') }}
                </a>
                <a href="{{ route('login') }}"  class="text-gray-700 hover:text-gray-900">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">
                    {{ __('Register') }}
                </a>
            </div>
        </div>
    @endguest

    {{-- ================= Авторизований ================= --}}
    @auth
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                {{-- Лівий блок --}}
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}">
                            <x-application-logo class="h-9 w-auto text-gray-800"/>
                        </a>
                    </div>

                    <div class="hidden sm:flex space-x-8 ms-10">
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('clip.form')" :active="request()->routeIs('clip.form')">
                            {{ __('Search') }}
                        </x-nav-link>
                        <x-nav-link :href="route('clips.index')" :active="request()->routeIs('clips.*')">
                            {{ __('My clips') }}
                        </x-nav-link>
                    </div>
                </div>

                {{-- Правий дропдаун --}}
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                                {{ Auth::user()->name }}
                                <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z"/>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if (Route::has('profile.edit'))
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                                 onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    @endauth
</nav>

