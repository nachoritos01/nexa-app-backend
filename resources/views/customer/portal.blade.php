<x-layouts.app title="My Account">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            {{-- Sidebar (Desktop) --}}
            <aside class="hidden lg:block lg:w-72 shrink-0">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                    {{-- Avatar --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-primary-600 rounded-full flex items-center justify-center">
                            <span class="text-xl font-bold text-white">{{ $customer->initials }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $customer->name }}</p>
                            <p class="text-sm text-gray-500">{{ $customer->phone }}</p>
                        </div>
                    </div>

                    {{-- Nav --}}
                    <nav class="space-y-1">
                        <a href="{{ route('customer.orders') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'orders' || $tab === 'order-detail' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            My Orders
                        </a>
                        <a href="{{ route('customer.payments') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'payments' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Payments
                        </a>
                        @if(hasModule('loyalty'))
                        <a href="{{ route('customer.loyalty') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'loyalty' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            Loyalty
                            <span class="text-[10px] font-bold text-orange-500 bg-orange-50 px-1.5 py-0.5 rounded-full ml-auto">New</span>
                        </a>
                        @endif
                        <a href="{{ route('customer.addresses') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'addresses' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Addresses
                        </a>
                        <a href="{{ route('customer.profile') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'profile' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Profile
                        </a>
                        <a href="{{ route('customer.settings') }}"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors {{ $tab === 'settings' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Settings
                        </a>
                    </nav>

                    {{-- Logout --}}
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <form method="POST" action="{{ route('customer.logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition-colors w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Sign Out
                            </button>
                        </form>
                    </div>

                    {{-- Legal Links --}}
                    <div class="mt-4 px-4 space-y-1">
                        <a href="{{ route('terms') }}" target="_blank" class="block text-xs text-gray-400 hover:text-gray-600 transition-colors">Terms and Conditions</a>
                        <a href="{{ route('usage-policies') }}" target="_blank" class="block text-xs text-gray-400 hover:text-gray-600 transition-colors">Usage Policies</a>
                    </div>
                </div>
            </aside>

            {{-- Mobile Tabs --}}
            <div class="lg:hidden mb-6 -mx-4 px-4 overflow-x-auto">
                <div class="flex gap-2 min-w-max pb-2">
                    <a href="{{ route('customer.orders') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'orders' || $tab === 'order-detail' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Orders
                    </a>
                    <a href="{{ route('customer.payments') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'payments' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Payments
                    </a>
                    @if(hasModule('loyalty'))
                    <a href="{{ route('customer.loyalty') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'loyalty' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Loyalty <span class="text-[10px] font-bold text-orange-500 bg-orange-50 px-1 py-0.5 rounded-full">New</span>
                    </a>
                    @endif
                    <a href="{{ route('customer.addresses') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'addresses' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Addresses
                    </a>
                    <a href="{{ route('customer.profile') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'profile' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Profile
                    </a>
                    <a href="{{ route('customer.settings') }}" class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $tab === 'settings' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                        Settings
                    </a>
                </div>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @include('customer.tabs.' . $tab)
            </div>
        </div>

        {{-- Mobile logout --}}
        <div class="lg:hidden mt-8 text-center">
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                    Sign Out
                </button>
            </form>
            <div class="mt-3 flex justify-center gap-4">
                <a href="{{ route('terms') }}" target="_blank" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Terms and Conditions</a>
                <a href="{{ route('usage-policies') }}" target="_blank" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Usage Policies</a>
            </div>
        </div>
    </div>
</x-layouts.app>
