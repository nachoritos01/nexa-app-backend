<x-layouts.saas title="SaaS Template">
    {{-- Hero --}}
    <section class="gradient-hero text-white py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight">
                The operating system<br>for your <span class="text-blue-200">business</span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto">
                All-in-one software to manage orders, production, payments, and customers. Designed for service businesses and custom order shops.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-600 text-lg font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">
                    Start free 14-day trial
                </a>
                <a href="#features" class="inline-flex items-center justify-center px-8 py-4 bg-white/10 text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition border border-white/30">
                    See features
                </a>
            </div>
            <p class="mt-4 text-sm text-blue-200">No credit card required</p>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Everything you need for your business</h2>
                <p class="mt-4 text-lg text-gray-600">Professional tools from day one</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Order Management</h3>
                    <p class="text-gray-600 text-sm">Step-by-step wizard: customer, items, delivery, payments, and summary. Everything organized.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Production Tracking</h3>
                    <p class="text-gray-600 text-sm">Production checklist, status tracking, and integrated shipping labels.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Quotes</h3>
                    <p class="text-gray-600 text-sm">Quick quotes with configurable pricing. PDF generation included.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Payments</h3>
                    <p class="text-gray-600 text-sm">Payment links, deposits, pending balances. Accept card, bank transfer, and more.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Customer Portal</h3>
                    <p class="text-gray-600 text-sm">Your customers check their orders, payments, and addresses from their own portal.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="p-6 rounded-2xl border border-gray-100 hover:border-blue-100 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Dashboard</h3>
                    <p class="text-gray-600 text-sm">Real-time metrics: orders, payments, balances, and production in one place.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonial --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <blockquote class="text-xl sm:text-2xl text-gray-700 italic">
                "This platform helped us organize our entire process, from quoting to delivery. We save hours every week."
            </blockquote>
            <div class="mt-6">
                <p class="font-semibold text-gray-900">Happy Customer</p>
                <p class="text-sm text-gray-500">Service Business Owner</p>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 bg-blue-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Start your free trial</h2>
            <p class="text-lg text-blue-100 mb-8">14 days free. No credit card required. Cancel anytime.</p>
            <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 text-lg font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">
                Create free account
            </a>
        </div>
    </section>
</x-layouts.saas>
