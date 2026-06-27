<x-layouts.app title="Contact">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-2">
                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Contact</h1>
            </div>
            <p class="text-gray-600">Have questions? Reach out through any of these channels.</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Contact Methods -->
            <div class="space-y-6">
                <div class="card">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Contact Methods
                    </h2>

                    <div class="space-y-4">
                        <!-- Email -->
                        <a
                            href="mailto:{{ config('business.contact.email') }}"
                            class="flex items-center gap-4 p-4 bg-primary-50 rounded-xl hover:bg-primary-100 transition-colors"
                        >
                            <div class="w-12 h-12 bg-primary-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Email</div>
                                <div class="text-sm text-gray-600">For detailed inquiries</div>
                                <div class="text-primary-600 font-medium">{{ config('business.contact.email') }}</div>
                            </div>
                        </a>

                        <!-- Phone -->
                        <a
                            href="tel:{{ config('business.contact.phone') }}"
                            class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
                        >
                            <div class="w-12 h-12 bg-gray-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Phone</div>
                                <div class="text-sm text-gray-600">Direct calls</div>
                                <div class="text-gray-700 font-medium">{{ config('business.contact.phone') }}</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="card">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Business Hours
                    </h2>

                    <div class="space-y-2">
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Monday - Friday</span>
                            <span class="font-medium text-gray-900">{{ config('business.hours.weekdays', '9:00 - 18:00') }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <span class="text-gray-600">Saturday</span>
                            <span class="font-medium text-gray-900">{{ config('business.hours.saturday', '10:00 - 14:00') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Sunday</span>
                            <span class="font-medium text-gray-500">{{ config('business.hours.sunday', 'Closed') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="space-y-6">
                <div class="card">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Location
                    </h2>

                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-primary-600 font-bold text-sm">1</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Main Office</h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ config('business.location.city', 'City') }}, {{ config('business.location.state', 'State') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Placeholder -->
                <div class="card">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Map
                    </h2>

                    <div class="aspect-video bg-gray-100 rounded-xl flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="text-sm">Interactive Map</p>
                            <p class="text-xs">Coming soon</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
