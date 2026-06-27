<x-filament-panels::page>
    <div class="space-y-6">

        {{-- New token alert --}}
        @if ($newToken)
            <div class="rounded-xl border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 p-6">
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">
                    Token Generated
                </h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mb-4">
                    Copy this token now. It will not be shown again.
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 block p-3 bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700 text-sm font-mono text-gray-900 dark:text-white break-all select-all">{{ $newToken }}</code>
                </div>
                <button type="button" wire:click="dismissToken"
                    class="mt-3 text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 underline">
                    Got it, I've copied it
                </button>
            </div>
        @endif

        {{-- Generate token --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                Generate API Token
            </h3>
            <p class="text-sm text-gray-500 dark:text-white mb-4">
                Tokens allow access to your account's REST API v1.
                Use the header <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">Authorization: Bearer {token}</code>.
            </p>

            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Token name</label>
                    <input type="text" wire:model="tokenName" placeholder="e.g. Zapier, Shopify, My App"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <x-filament::button wire:click="generateToken" wire:loading.attr="disabled">
                    Generate Token
                </x-filament::button>
            </div>
            @error('tokenName')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Token list --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Active Tokens
            </h3>

            @php $tokens = $this->getTokens(); @endphp

            @if (count($tokens) === 0)
                <p class="text-sm text-gray-500 dark:text-white">
                    No active tokens. Generate one to start using the API.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Name</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Token</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Created</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Last used</th>
                                <th class="py-2 px-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tokens as $token)
                                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-2 px-3 text-gray-900 dark:text-white font-medium">{{ $token['name'] }}</td>
                                    <td class="py-2 px-3">
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-600 dark:text-white">{{ $token['preview'] }}</code>
                                    </td>
                                    <td class="py-2 px-3 text-gray-500 dark:text-white">{{ $token['created_at'] }}</td>
                                    <td class="py-2 px-3 text-gray-500 dark:text-white">{{ $token['last_used_at'] ?? 'Never' }}</td>
                                    <td class="py-2 px-3 text-right">
                                        <button type="button"
                                            wire:click="revokeToken({{ $token['id'] }})"
                                            wire:confirm="Revoke this token? Applications using it will stop working."
                                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-xs font-medium">
                                            Revoke
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- API Info --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                Quick Reference
            </h3>
            <div class="text-sm text-gray-600 dark:text-white space-y-2">
                <p><strong>Base URL:</strong> <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ url('/api/v1') }}</code></p>
                <p><strong>Endpoints:</strong></p>
                <ul class="list-disc list-inside ml-2 space-y-1">
                    <li><code class="text-xs">GET /api/v1/orders</code> — List orders</li>
                    <li><code class="text-xs">GET /api/v1/orders/{id}</code> — View order</li>
                    <li><code class="text-xs">POST /api/v1/orders</code> — Create order</li>
                    <li><code class="text-xs">GET /api/v1/customers</code> — List customers</li>
                    <li><code class="text-xs">GET /api/v1/items</code> — List items</li>
                    <li><code class="text-xs">GET /api/v1/payments</code> — List payments</li>
                </ul>
                <p><strong>Rate Limit:</strong> 300 requests/min (Pro Plan)</p>
            </div>
        </div>

    </div>
</x-filament-panels::page>
