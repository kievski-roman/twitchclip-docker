<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="{ dark: $store.theme.dark }"   >
{{----}}
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SubGenius' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js','resources/js/theme.js'])
</head>
<body
    class="bg-gradient-to-br from-gray-100 via-purple-100 to-blue-100">

@include('layouts.navigation')   {{-- якщо є --}}
<main class="flex-1">
    {{ $slot }}
</main>

<footer class="text-center py-6 text-sm text-gray-600 dark:text-gray-400">
    &copy; {{ now()->year }} SubGenius. All rights reserved.
</footer>
</body>
</html>
