<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="{ dark: $store.theme.dark }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/alpine.js', 'resources/js/theme.js'])
    @endif
</head>

<body class="bg-gradient-to-br from-gray-100 via-purple-100 to-blue-100
             dark:from-zinc-900 dark:via-purple-900 dark:to-black
             text-black dark:text-white min-h-screen flex flex-col">



{{-- ===== основна зона контенту ===== --}}
<x-app-layout>
    {{ $slot }}
</x-app-layout>


</body>
</html>
