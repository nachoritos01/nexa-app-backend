<x-layouts.saas :title="$title">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $title }}</h1>
            <div class="h-1 w-20 bg-blue-600 rounded"></div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="prose prose-blue max-w-none">
                {!! $content !!}
            </div>
        </div>

        <div class="mt-8">
            <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to home
            </a>
        </div>
    </div>
</x-layouts.saas>
