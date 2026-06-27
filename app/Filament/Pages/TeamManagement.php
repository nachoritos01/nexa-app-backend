<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\NotifiesNearLimit;
use App\Models\User;
use App\Notifications\TeamInviteNotification;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class TeamManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use NotifiesNearLimit;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $title = 'Team';

    protected static ?string $navigationLabel = 'Team';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 96;

    protected static string $view = 'filament.pages.team-management';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public function table(Table $table): Table
    {
        $tenant = currentTenant();

        return $table
            ->query(
                User::query()
                    ->join('tenant_user', 'users.id', '=', 'tenant_user.user_id')
                    ->where('tenant_user.tenant_id', $tenant?->id)
                    ->select('users.*')
            )
            ->defaultSort('users.created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot_role')
                    ->label('Role')
                    ->badge()
                    ->state(function (User $record) use ($tenant): string {
                        return $record->tenants()
                            ->where('tenant_id', $tenant?->id)
                            ->first()?->pivot?->role ?? 'sin rol';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'danger',
                        'admin' => 'warning',
                        'manager' => 'success',
                        'staff' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('joined_at')
                    ->label('Since')
                    ->state(function (User $record) use ($tenant): ?string {
                        return $record->tenants()
                            ->where('tenant_id', $tenant?->id)
                            ->first()?->pivot?->created_at;
                    })
                    ->dateTime('Y-m-d'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('invite')
                    ->label('Invite member')
                    ->icon('heroicon-o-plus')
                    ->disabled(fn (): bool => currentTenant()?->isAtLimit('users') ?? true)
                    ->form([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required(),
                        Select::make('role')
                            ->label('Role')
                            ->options($this->getAssignableRoles())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->inviteMember($data['email'], $data['role']);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('change_role')
                    ->label('Change role')
                    ->icon('heroicon-o-pencil')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id())
                    ->form(fn (User $record): array => [
                        Select::make('role')
                            ->label('New role')
                            ->options($this->getAssignableRoles())
                            ->default(
                                $record->tenants()
                                    ->where('tenant_id', currentTenant()?->id)
                                    ->first()?->pivot?->role
                            )
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->changeRole($record, $data['role']);
                    }),
                Tables\Actions\Action::make('resend_invite')
                    ->label('Resend invite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('gray')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id() || $record->last_login_at !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Resend invite')
                    ->modalDescription(fn (User $record): string => "A new access link will be generated for {$record->name}.")
                    ->action(function (User $record): void {
                        $this->resendInvite($record);
                    }),
                Tables\Actions\Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading('Remove team member')
                    ->modalDescription(fn (User $record): string => "{$record->name} will be removed from the team. Their account will not be deleted.")
                    ->action(function (User $record): void {
                        $this->removeMember($record);
                    }),
            ]);
    }

    /** @return array<string, string> */
    private function getAssignableRoles(): array
    {
        $roles = [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
        ];

        if (auth()->user()?->hasRole('owner')) {
            $roles = ['owner' => 'Owner'] + $roles;
        }

        return $roles;
    }

    private function inviteMember(string $email, string $role): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        // Server-side limit check
        if ($tenant->isAtLimit('users')) {
            Notification::make()
                ->title('Limit reached')
                ->body('You have reached the user limit for your plan. Upgrade your plan to add more members.')
                ->danger()
                ->send();

            return;
        }

        // Check if already a member
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $tenant->users()->where('user_id', $existingUser->id)->exists()) {
            Notification::make()
                ->title('Already a member')
                ->body("{$email} is already part of this team.")
                ->warning()
                ->send();

            return;
        }

        $isNewUser = false;
        $resetUrl = null;

        if ($existingUser) {
            $user = $existingUser;
        } else {
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(12),
            ]);
            $isNewUser = true;

            /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
            $broker = Password::broker();
            $token = $broker->createToken($user);
            $resetUrl = Filament::getResetPasswordUrl($token, $user);
        }

        $tenant->users()->attach($user->id, ['role' => $role]);
        $tenant->clearUsageCache();

        $user->notify(new TeamInviteNotification(
            tenantName: $tenant->name,
            role: $role,
            isNewUser: $isNewUser,
            resetUrl: $resetUrl,
        ));

        $notification = Notification::make()
            ->title('Member invited')
            ->success();

        if ($resetUrl) {
            $notification
                ->body("Account created for {$email}. Share this link so they can set their password:")
                ->persistent()
                ->actions([
                    NotificationAction::make('copy_link')
                        ->label('Copy link')
                        ->icon('heroicon-o-clipboard')
                        ->extraAttributes([
                            'x-on:click.prevent' => "navigator.clipboard.writeText('" . Str::replace("'", "\\'", $resetUrl) . "'); \$el.innerText = 'Copied!'",
                        ]),
                    NotificationAction::make('open_link')
                        ->label('Open')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url($resetUrl)
                        ->openUrlInNewTab()
                        ->color('gray'),
                ]);
        } else {
            $notification->body("{$email} has been added to the team as {$role}.");
        }

        $notification->send();

        $this->checkNearLimit('users');
    }

    private function resendInvite(User $record): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($record);
        $resetUrl = Filament::getResetPasswordUrl($token, $record);

        $pivotRole = $record->tenants()
            ->where('tenant_id', $tenant->id)
            ->first()?->pivot?->role ?? 'member';

        $record->notify(new TeamInviteNotification(
            tenantName: $tenant->name,
            role: $pivotRole,
            isNewUser: true,
            resetUrl: $resetUrl,
        ));

        Notification::make()
            ->title('Invite resent')
            ->body("New link generated for {$record->email}. Share this link:")
            ->success()
            ->persistent()
            ->actions([
                NotificationAction::make('copy_link')
                    ->label('Copiar link')
                    ->icon('heroicon-o-clipboard')
                    ->extraAttributes([
                        'x-on:click.prevent' => "navigator.clipboard.writeText('" . Str::replace("'", "\\'", $resetUrl) . "'); \$el.innerText = 'Copied!'",
                    ]),
                NotificationAction::make('open_link')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url($resetUrl)
                    ->openUrlInNewTab()
                    ->color('gray'),
            ])
            ->send();
    }

    private function changeRole(User $record, string $newRole): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        // Only owner can assign owner role
        if ($newRole === 'owner' && ! auth()->user()?->hasRole('owner')) {
            Notification::make()
                ->title('No permission')
                ->body('Only the owner can assign the owner role.')
                ->danger()
                ->send();

            return;
        }

        // Admin cannot change owner's role
        $currentRole = $record->tenants()
            ->where('tenant_id', $tenant->id)
            ->first()?->pivot?->role;

        if ($currentRole === 'owner' && ! auth()->user()?->hasRole('owner')) {
            Notification::make()
                ->title('No permission')
                ->body('You cannot change the owner\'s role.')
                ->danger()
                ->send();

            return;
        }

        $tenant->users()->updateExistingPivot($record->id, ['role' => $newRole]);

        Notification::make()
            ->title('Role updated')
            ->body("{$record->name}'s role has been updated to {$newRole}.")
            ->success()
            ->send();
    }

    private function removeMember(User $record): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        // Cannot remove self
        if ($record->id === auth()->id()) {
            Notification::make()
                ->title('Action not allowed')
                ->body('You cannot remove yourself from the team.')
                ->danger()
                ->send();

            return;
        }

        // Cannot leave 0 members
        if ($tenant->users()->count() <= 1) {
            Notification::make()
                ->title('Action not allowed')
                ->body('The team must have at least one member.')
                ->danger()
                ->send();

            return;
        }

        $tenant->users()->detach($record->id);
        $tenant->clearUsageCache();

        Notification::make()
            ->title('Member removed')
            ->body("{$record->name} has been removed from the team.")
            ->success()
            ->send();
    }
}
