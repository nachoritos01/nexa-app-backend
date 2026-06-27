<div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Settings</h2>

    {{-- Notifications --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Notifications</h3>
        <form method="POST" action="{{ route('customer.notifications.update') }}">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Payment confirmations</p>
                        <p class="text-xs text-gray-500">Receive a confirmation when a payment is recorded</p>
                    </div>
                    <input type="hidden" name="notifications_payment" value="0">
                    <input type="checkbox" name="notifications_payment" value="1"
                           {{ $customer->notifications_payment ? 'checked' : '' }}
                           class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                </label>

                <label class="flex items-center justify-between cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Promotions and updates</p>
                        <p class="text-xs text-gray-500">Special offers and new products</p>
                    </div>
                    <input type="hidden" name="notifications_promos" value="0">
                    <input type="checkbox" name="notifications_promos" value="1"
                           {{ $customer->notifications_promos ? 'checked' : '' }}
                           class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                </label>
            </div>

            <button type="submit" class="mt-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-colors">
                Save Preferences
            </button>
        </form>
    </div>

    {{-- Security --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Security</h3>
        <div class="space-y-3">
            <a href="{{ route('customer.profile') }}" class="flex items-center justify-between py-2 text-sm text-gray-600 hover:text-primary-600 transition-colors">
                <span>Change password</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    {{-- Danger zone --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-red-100" x-data="{ confirmDelete: false }">
        <h3 class="text-sm font-semibold text-red-600 mb-4">Danger zone</h3>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">Delete account</p>
                    <p class="text-xs text-gray-500">Your personal data will be deleted. Order history is kept anonymized.</p>
                </div>
                <button @click="confirmDelete = true" class="text-sm text-red-600 hover:text-red-700 font-medium">
                    Delete
                </button>
            </div>
        </div>

        {{-- Delete confirmation modal --}}
        <div x-show="confirmDelete" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" @keydown.escape.window="confirmDelete = false">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.away="confirmDelete = false">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Delete your account?</h3>
                <p class="text-sm text-gray-600 mb-4">This action cannot be undone. Enter your password to confirm.</p>

                <form method="POST" action="{{ route('customer.account.delete') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="delete_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="delete_password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-xl transition-colors">
                            Yes, Delete Account
                        </button>
                        <button type="button" @click="confirmDelete = false" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
