<x-guest-layout>
    {{-- Hero --}}
    <section class="flex-1 py-16 px-4 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-4xl font-extrabold mb-4">Create AI-Generated Subtitles in Seconds</h2>
            <p class="text-lg mb-6">
                SubGenius uses neural networks to automatically generate subtitles for your content — from long-form videos to TikToks and YouTube Shorts.
            </p>
            <a href="auth.html" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded">Get Started</a>
        </div>
    </section>

    <!-- Features -->
    <section class="py-16 px-4 text-center">
        <div class="max-w-6xl mx-auto text-center">
            <h3 class="text-2xl font-bold mb-10">Why SubGenius?</h3>
            <div class="grid md:grid-cols-3 gap-8 text-left">
                <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg shadow">
                    <h4 class="font-semibold text-lg mb-2">🚀 Fast Processing</h4>
                    <p>Process long videos in just minutes using cutting-edge AI models. Save hours of manual work.</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg shadow">
                    <h4 class="font-semibold text-lg mb-2">🎯 Perfect for Shorts</h4>
                    <p>Automatically find and clip the most engaging video moments — ready for YouTube Shorts or TikTok with subtitles.</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg shadow">
                    <h4 class="font-semibold text-lg mb-2">💡 Time-Saving</h4>
                    <p>No need to manually add subtitles or cut highlights — our neural network does it for you.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Example Videos, Footer — додай за потребою --}}
    @push('scripts')
        @vite('resources/js/theme.js')
    @endpush
</x-guest-layout>
