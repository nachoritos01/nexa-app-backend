@php
    $eventOptions = $this->getEventOptions();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Webhook Configuration --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                Webhook Configuration
            </h3>
            <p class="text-sm text-gray-500 dark:text-white mb-6">
                Receive real-time notifications when events occur in your account.
            </p>

            {{-- Enable toggle --}}
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Enable webhooks</span>
                    <p class="text-xs text-gray-500 dark:text-white">HTTP POST notifications will be sent to your URL</p>
                </div>
                <button
                    type="button"
                    wire:click="$toggle('webhookEnabled')"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {{ $this->webhookEnabled ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600' }}"
                    role="switch"
                    aria-checked="{{ $this->webhookEnabled ? 'true' : 'false' }}"
                >
                    <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $this->webhookEnabled ? 'translate-x-5' : 'translate-x-0' }}"
                    ></span>
                </button>
            </div>

            <div class="{{ $this->webhookEnabled ? '' : 'opacity-60 pointer-events-none' }}">
                {{-- URL --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Webhook URL</label>
                    <input type="url" wire:model="webhookUrl" placeholder="https://your-server.com/webhook"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('webhookUrl')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Secret --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Signing Secret</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 block p-2.5 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-mono text-gray-900 dark:text-white select-all overflow-x-auto">{{ $webhookSecret }}</code>
                        <button type="button" wire:click="regenerateSecret"
                            class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-white bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                            Regenerate
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-white">
                        Use this secret to verify the <code>X-Webhook-Signature</code> header (HMAC SHA256).
                    </p>
                </div>

                {{-- Events --}}
                <div class="mb-4">
                    <span class="text-sm font-medium text-gray-900 dark:text-white mb-3 block">
                        Events to notify:
                    </span>
                    <div class="space-y-3">
                        @foreach($eventOptions as $value => $label)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       wire:model.live="webhookEvents"
                                       value="{{ $value }}"
                                       class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                                <div>
                                    <span class="text-sm text-gray-700 dark:text-white">{{ $label }}</span>
                                    <span class="text-xs text-gray-400 dark:text-white ml-1">({{ $value }})</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <x-filament::button wire:click="save" wire:loading.attr="disabled">
                Save Configuration
            </x-filament::button>

            @if ($webhookEnabled && $webhookUrl)
                <x-filament::button wire:click="testWebhook" wire:loading.attr="disabled" color="gray">
                    Send Test
                </x-filament::button>
            @endif
        </div>

        {{-- Webhook Logs --}}
        @php
            $logs = \App\Models\WebhookLog::where('tenant_id', currentTenant()?->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        @endphp

        @if ($logs->count() > 0)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Recent Deliveries
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Event</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Status</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Attempt</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-white">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                @php
                                    $statusColor = match(true) {
                                        $log->status_code === null => 'text-gray-400',
                                        $log->status_code >= 200 && $log->status_code < 300 => 'text-green-600 dark:text-green-400',
                                        $log->status_code >= 400 => 'text-red-600 dark:text-red-400',
                                        default => 'text-yellow-600 dark:text-yellow-400',
                                    };
                                @endphp
                                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-2 px-3 text-gray-900 dark:text-white">
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $log->event }}</code>
                                    </td>
                                    <td class="py-2 px-3 {{ $statusColor }} font-medium">
                                        {{ $log->status_code ?? 'Pending' }}
                                    </td>
                                    <td class="py-2 px-3 text-gray-500 dark:text-white">{{ $log->attempt }}</td>
                                    <td class="py-2 px-3 text-gray-500 dark:text-white">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
