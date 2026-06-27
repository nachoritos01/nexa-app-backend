<?php

namespace App\Filament\Pages;

use App\Models\PersonalAccessToken;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApiSettings extends Page
{
    protected static ?string $title = 'API';

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationLabel = 'API';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.api-settings';

    public string $tokenName = '';

    public ?string $newToken = null;

    public static function canAccess(): bool
    {
        if (! hasModule('api')) {
            return false;
        }

        $user = auth()->user();

        if (! $user?->can('settings.manage')) {
            return false;
        }

        $tenant = currentTenant();

        return $tenant && $tenant->plan === 'pro';
    }

    public function getTokens(): array
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return [];
        }

        return PersonalAccessToken::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'created_at' => $token->created_at->format('Y-m-d'),
                'preview' => '...' . substr($token->token, -8),
            ])
            ->toArray();
    }

    public function generateToken(): void
    {
        $this->validate([
            'tokenName' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $tenant = currentTenant();

        if (! $user || ! $tenant) {
            return;
        }

        $token = $user->createToken($this->tokenName, ['*']);

        $token->accessToken->update(['tenant_id' => $tenant->id]);

        $this->newToken = $token->plainTextToken;
        $this->tokenName = '';

        Notification::make()
            ->title('Token generated')
            ->body('Copy the token now. It will not be shown again.')
            ->warning()
            ->send();
    }

    public function revokeToken(int $tokenId): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        PersonalAccessToken::where('id', $tokenId)
            ->where('tenant_id', $tenant->id)
            ->delete();

        Notification::make()
            ->title('Token revoked')
            ->success()
            ->send();
    }

    public function dismissToken(): void
    {
        $this->newToken = null;
    }
}
