<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::section>
            <x-slot name="heading">Business Details</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Business name</label>
                    <input type="text" wire:model="businessName"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Slogan</label>
                    <input type="text" wire:model="slogan"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Phone</label>
                    <input type="text" wire:model="contactPhone"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Email</label>
                    <input type="email" wire:model="email"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Address</label>
                    <input type="text" wire:model="address"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-6">
            <x-slot name="heading">Business Logo</x-slot>

            @if ($this->canUploadLogo())
                <div class="space-y-4">
                    @if ($existingLogoPath)
                        <div class="flex items-center gap-4">
                            <img src="{{ Storage::disk('public')->url($existingLogoPath) }}"
                                 alt="Current logo"
                                 class="h-16 max-w-[200px] object-contain rounded border border-gray-200 dark:border-gray-600">
                            <button type="button" wire:click="removeLogo"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400">
                                Remove logo
                            </button>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                            {{ $existingLogoPath ? 'Change logo' : 'Upload logo' }}
                        </label>
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg"
                            class="block w-full text-sm text-gray-500 dark:text-white
                                   file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700
                                   hover:file:bg-primary-100 dark:file:bg-gray-600 dark:file:text-gray-200">
                        <p class="mt-1 text-xs text-gray-500 dark:text-white">PNG or JPG. Max 1MB. Recommended: 200x60px</p>
                    </div>

                    @if ($logo && !$errors->has('logo'))
                        <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                            New logo ready. Click "Save" to apply.
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-lock-closed class="w-5 h-5 flex-shrink-0" />
                        <span><strong>Upgrade for custom branding.</strong> Logo on PDFs and quotes is available from the Growth plan.</span>
                    </div>
                </div>
            @endif
        </x-filament::section>

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
