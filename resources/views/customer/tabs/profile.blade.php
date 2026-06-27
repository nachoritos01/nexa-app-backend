<div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">My Profile</h2>

    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        {{-- Avatar --}}
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
            <div class="w-20 h-20 bg-primary-600 rounded-full flex items-center justify-center">
                <span class="text-2xl font-bold text-white">{{ $customer->initials }}</span>
            </div>
            <div>
                <p class="text-lg font-semibold text-gray-900">{{ $customer->name }}</p>
                <p class="text-sm text-gray-500">Customer since {{ $customer->created_at->format('M Y') }}</p>
            </div>
        </div>

        {{-- Profile form --}}
        <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="tel" id="phone" value="{{ $customer->phone }}" disabled
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Phone number cannot be changed. Contact support if you need to update it.</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}" placeholder="tu@email.com"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                Save Changes
            </button>
        </form>
    </div>

    {{-- Change password --}}
    <div class="bg-white rounded-2xl shadow-sm p-6" x-data="{ open: false }">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Change Password</h3>
            <button @click="open = !open" type="button" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                <span x-text="open ? 'Cancel' : 'Change'"></span>
            </button>
        </div>

        <form method="POST" action="{{ route('customer.password.update') }}" class="mt-4 space-y-4" x-show="open" x-transition>
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                @error('current_password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                Update Password
            </button>
        </form>
    </div>
</div>
