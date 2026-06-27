<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->required()
                    ->minValue(0.01)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('fillBalance')
                            ->label('Pay balance')
                            ->icon('heroicon-o-banknotes')
                            ->action(function (Forms\Set $set) {
                                /** @var Order $order */
                                $order = $this->getOwnerRecord();
                                $set('amount', number_format($order->balance, 2, '.', ''));
                            }),
                    )
                    ->rules([
                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                            /** @var Order $order */
                            $order = $this->getOwnerRecord();
                            $balance = $order->balance;
                            if ((float) $value > $balance) {
                                $fail("Amount (\${$value}) cannot exceed balance (\${$balance}).");
                            }
                        },
                    ]),

                Forms\Components\Select::make('method')
                    ->label('Payment Method')
                    ->options(PaymentMethod::options())
                    ->required()
                    ->default(PaymentMethod::Cash->value),

                Forms\Components\TextInput::make('reference')
                    ->label('Reference')
                    ->maxLength(100)
                    ->placeholder('Transfer number, voucher, etc.'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),

                Forms\Components\DateTimePicker::make('received_at')
                    ->label('Payment Date')
                    ->required()
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (PaymentMethod $state): string => $state->color())
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('received_at')
                    ->label('Payment Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('received_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Payment')
                    ->visible(function (): bool {
                        /** @var Order $order */
                        $order = $this->getOwnerRecord();

                        return $order->balance > 0;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['received_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
