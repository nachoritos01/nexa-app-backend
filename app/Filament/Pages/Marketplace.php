<?php

namespace App\Filament\Pages;

use App\Models\Plugin;
use App\Services\PluginBillingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Laravel\Cashier\Exceptions\IncompletePayment;

class Marketplace extends Page
{
    protected static ?string $title = 'Plugin Marketplace';

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 98;

    protected static string $view = 'filament.pages.marketplace';

    public bool $showConfirmModal = false;

    public ?int $pendingPluginId = null;

    public ?string $pendingAction = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('plugins.manage') ?? false;
    }

    public function getPluginsByCategory(): Collection
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return collect();
        }

        $activePluginSlugs = $tenant->plugins()
            ->wherePivot('is_active', true)
            ->pluck('slug')
            ->all();

        return Plugin::active()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Plugin $plugin) => $plugin->isAvailable())
            ->map(function (Plugin $plugin) use ($tenant, $activePluginSlugs) {
                $plugin->is_enabled_for_tenant = in_array($plugin->slug, $activePluginSlugs, true);
                $plugin->is_included = $plugin->isIncludedInPlan($tenant->plan);

                return $plugin;
            })
            ->groupBy('category');
    }

    public function tenantHasSubscription(): bool
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return false;
        }

        return $tenant->subscribed_at !== null || $tenant->subscription('default') !== null;
    }

    public function togglePlugin(int $pluginId): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $plugin = Plugin::find($pluginId);

        if (! $plugin || ! $plugin->isAvailable()) {
            return;
        }

        $isActive = $tenant->hasPlugin($plugin->slug);
        $isPaid = ! $plugin->is_free && ! $plugin->isIncludedInPlan($tenant->plan);

        // For paid plugins, show confirmation modal
        if ($isPaid) {
            if (! $isActive && ! $this->tenantHasSubscription()) {
                Notification::make()
                    ->title('Subscription required')
                    ->body('You need an active subscription to add paid plugins.')
                    ->warning()
                    ->send();

                return;
            }

            $this->pendingPluginId = $pluginId;
            $this->pendingAction = $isActive ? 'deactivate' : 'activate';
            $this->showConfirmModal = true;

            return;
        }

        // Free/included plugins — toggle directly
        $this->executeToggle($tenant, $plugin, $isActive);
    }

    public function confirmToggle(): void
    {
        $tenant = currentTenant();

        if (! $tenant || ! $this->pendingPluginId) {
            $this->closeConfirmModal();

            return;
        }

        $plugin = Plugin::find($this->pendingPluginId);

        if (! $plugin) {
            $this->closeConfirmModal();

            return;
        }

        $isActive = $this->pendingAction === 'deactivate';
        $this->closeConfirmModal();
        $this->executeToggle($tenant, $plugin, $isActive);
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal = false;
        $this->pendingPluginId = null;
        $this->pendingAction = null;
    }

    public function getPendingPlugin(): ?Plugin
    {
        if (! $this->pendingPluginId) {
            return null;
        }

        return Plugin::find($this->pendingPluginId);
    }

    private function executeToggle(\App\Models\Tenant $tenant, Plugin $plugin, bool $isActive): void
    {
        $billing = app(PluginBillingService::class);

        try {
            if ($isActive) {
                $billing->deactivatePlugin($tenant, $plugin);

                Notification::make()
                    ->title("{$plugin->name} deactivated")
                    ->success()
                    ->send();
            } else {
                $billing->activatePlugin($tenant, $plugin);

                Notification::make()
                    ->title("{$plugin->name} activated")
                    ->success()
                    ->send();
            }
        } catch (IncompletePayment $e) {
            Notification::make()
                ->title('Payment requires action')
                ->body('Please complete the payment to activate this plugin.')
                ->warning()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Subscription required')
                ->body('You need an active subscription to add paid plugins.')
                ->danger()
                ->send();
        }

        // Clear memoized hasModule cache
        hasModule(null);
    }
}
