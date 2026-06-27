<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class TenantSettings extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Business Settings';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'My Business';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 97;

    protected static string $view = 'filament.pages.tenant-settings';

    public string $businessName = '';

    public string $slogan = '';

    public string $contactPhone = '';

    public string $email = '';

    public string $address = '';

    /** @var TemporaryUploadedFile|string|null */
    public $logo = null;

    public ?string $existingLogoPath = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];
        $this->businessName = $settings['business_name'] ?? $tenant->name ?? '';
        $this->slogan = $settings['slogan'] ?? '';
        $this->contactPhone = $settings['contact_phone'] ?? $settings['phone'] ?? '';
        $this->email = $settings['email'] ?? '';
        $this->address = $settings['address'] ?? $settings['city'] ?? '';
        $this->existingLogoPath = $settings['logo_path'] ?? null;
    }

    public function canUploadLogo(): bool
    {
        $tenant = currentTenant();

        return $tenant && $tenant->plan !== 'starter';
    }

    public function save(): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];
        $settings['business_name'] = $this->businessName;
        $settings['slogan'] = $this->slogan;
        $settings['contact_phone'] = $this->contactPhone;
        $settings['email'] = $this->email;
        $settings['address'] = $this->address;

        // Handle logo upload (only for Growth+)
        if ($this->canUploadLogo() && $this->logo instanceof TemporaryUploadedFile) {
            // Delete old logo if exists
            if ($this->existingLogoPath && Storage::disk('public')->exists($this->existingLogoPath)) {
                Storage::disk('public')->delete($this->existingLogoPath);
            }

            $path = $this->logo->store('tenants', 'public');
            $settings['logo_path'] = $path;
            $this->existingLogoPath = $path;
            $this->logo = null;
        }

        $tenant->update(['settings' => $settings]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    public function removeLogo(): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];

        if (! empty($settings['logo_path']) && Storage::disk('public')->exists($settings['logo_path'])) {
            Storage::disk('public')->delete($settings['logo_path']);
        }

        unset($settings['logo_path']);
        $tenant->update(['settings' => $settings]);
        $this->existingLogoPath = null;

        Notification::make()
            ->title('Logo removed')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }
}
