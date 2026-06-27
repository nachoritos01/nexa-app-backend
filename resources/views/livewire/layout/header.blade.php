<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-primary-800 hidden sm:block">{{ config('business.name') }}</span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'nav-link-active' : '' }}">
                    Home
                </a>
                <a href="{{ route('contact') }}" class="nav-link {{ request()->routeIs('contact') ? 'nav-link-active' : '' }}">
                    Contact
                </a>
            </nav>

            <!-- Customer Account + Cart -->
            <div class="flex items-center gap-4">
                @if(auth('customer')->check())
                    <a href="{{ route('customer.orders') }}" class="hidden md:flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Account
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" class="hidden md:flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Sign In
                    </a>
                @endif

                <!-- Mobile Menu Button -->
                <button
                    wire:click="toggleMobileMenu"
                    class="md:hidden p-2 text-gray-600 hover:text-primary-600"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($mobileMenuOpen)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        @endif
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        @if($mobileMenuOpen)
            <nav class="md:hidden py-4 border-t border-gray-100 animate-fade-in">
                <div class="flex flex-col gap-2">
                    <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                        Home
                    </a>
                    <a href="{{ route('contact') }}" class="px-4 py-2 rounded-lg {{ request()->routeIs('contact') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                        Contact
                    </a>
                    <div class="border-t border-gray-100 mt-2 pt-2">
                        @if(auth('customer')->check())
                            <a href="{{ route('customer.orders') }}" class="px-4 py-2 rounded-lg flex items-center gap-2 {{ request()->routeIs('customer.*') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                My Account
                            </a>
                        @else
                            <a href="{{ route('customer.login') }}" class="px-4 py-2 rounded-lg flex items-center gap-2 text-gray-600 hover:bg-gray-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Sign In
                            </a>
                        @endif
                    </div>
                </div>
            </nav>
        @endif
    </div>
</header>
