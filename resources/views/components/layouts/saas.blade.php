<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SaaS Template' }} — Business management platform</title>
    <meta name="description" content="All-in-one SaaS platform for order management, payments, and customer portal.">

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 min-h-screen font-sans antialiased">
    {{-- Navbar --}}
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900">
                    Saas<span class="text-blue-600">App</span>
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('home') }}#features" class="text-sm text-gray-600 hover:text-gray-900">Features</a>
                    <a href="{{ route('saas.pricing') }}" class="text-sm text-gray-600 hover:text-gray-900">Pricing</a>
                    <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-gray-600 hover:text-gray-900">Log in</a>
                    <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        Free trial
                    </a>
                </div>

                {{-- Mobile menu button --}}
                <div class="md:hidden" x-data="{ open: false }">
                    <button @click="open = !open" class="text-gray-600 hover:text-gray-900">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute top-16 left-0 right-0 bg-white border-b border-gray-100 shadow-lg p-4 space-y-3">
                        <a href="{{ route('home') }}#features" class="block text-sm text-gray-600 hover:text-gray-900">Features</a>
                        <a href="{{ route('saas.pricing') }}" class="block text-sm text-gray-600 hover:text-gray-900">Pricing</a>
                        <a href="{{ route('filament.admin.auth.login') }}" class="block text-sm text-gray-600 hover:text-gray-900">Log in</a>
                        <a href="{{ route('filament.admin.auth.register') }}" class="block text-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">Free trial</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <span class="text-lg font-bold text-white">Saas<span class="text-blue-400">App</span></span>
                    <p class="mt-2 text-sm">All-in-one business management platform.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-3">Product</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="{{ route('saas.pricing') }}" class="hover:text-white transition">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-3">Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="mailto:support@example.com" class="hover:text-white transition">support@example.com</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-3">Legal</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('saas.terms') }}" class="hover:text-white transition">Terms and Conditions</a></li>
                        <li><a href="{{ route('saas.policies') }}" class="hover:text-white transition">Usage Policies</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-sm text-center">
                &copy; {{ date('Y') }} SaasApp. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
