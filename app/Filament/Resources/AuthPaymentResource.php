<?php

namespace App\Filament\Resources;

use App\Constants\PayStackTransactionStatus;
use App\Filament\Resources\AuthPaymentResource\Pages;
use App\Models\AuthPayment;
use App\Models\Client;
use App\Services\PaystackService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuthPaymentResource extends Resource
{
    protected static ?string $model = AuthPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('auth_email')
                    ->label('Client')
                    ->relationship('client', 'auth_email', function ($query) {
                        return $query->where('auth_email', '!=', null);
                    })
                    ->required(),

                Forms\Components\TextInput::make('auth_phone')
                    ->label('Phone Number')
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('auth_phone')
                    ->label('Phone Number'),
                Tables\Columns\TextColumn::make('authorization_url')
                    ->copyable()
                    ->copyMessage('Auth url copied'),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->hidden(function (AuthPayment $record) {
                        return $record->status === PayStackTransactionStatus::SUCCESS;
                    })
                    ->requiresConfirmation()
                    ->action(function (AuthPayment $record) {
                        $ref = $record->reference;
                        $authService = app(PaystackService::class);
                        $response = $authService->verify($ref);

                        \Illuminate\Log\log('response', [$response]);

                        if ($response['data']['status'] !== PayStackTransactionStatus::SUCCESS) {
                            $record->status = $response['data']['status'];
                            $record->save();
                            Notification::make('failed_to_verify')
                                ->title('Failed to verify payment.')
                                ->danger()
                                ->send();

                            return;
                        }


                        $client = Client::query()
                            ->firstWhere('auth_email', $record->auth_email);


                        $client->update([
                            'auth_res' => json_encode($response['data']['authorization'])
                        ]);

                        $record->status = 'success';
                        $record->save();
                        Notification::make('verified_successfully')
                            ->title('Payment verified successfully.')
                            ->success()
                            ->send();

                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthPayments::route('/'),
        ];
    }
}
