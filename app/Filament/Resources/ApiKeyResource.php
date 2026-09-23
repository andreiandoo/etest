<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use BackedEnum;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Cheile emise.
 *
 * Nu se creează și nu se editează de aici: o cheie se emite din clientul ei,
 * fiindcă valoarea în clar apare o singură dată, la emitere. Aici se vede ce
 * există, cu ce permisiuni, și se revocă.
 */
class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static ?string $slug = 'chei-api';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'Platformă';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'cheie API';

    protected static ?string $pluralModelLabel = 'chei API';

    protected static ?string $navigationLabel = 'Chei API';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),

                Tables\Columns\TextColumn::make('key_prefix')
                    ->label('Prefix')
                    ->fontFamily('mono')
                    ->description('restul cheii nu se păstrează'),

                Tables\Columns\TextColumn::make('scopes')
                    ->label('Permisiuni')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ApiClientResource::scopeOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->state(function (ApiKey $record): string {
                        if ($record->revoked_at !== null) {
                            return 'revocată';
                        }

                        if ($record->expires_at !== null && $record->expires_at->isPast()) {
                            return 'expirată';
                        }

                        return 'activă';
                    })
                    ->color(fn (string $state): string => $state === 'activă' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Folosită ultima dată')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('niciodată')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expiră')
                    ->dateTime('d.m.Y')
                    ->placeholder('fără expirare'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('doar_active')
                    ->label('Doar chei active')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('revoked_at')
                        ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))),
            ])
            ->actions([
                Actions\Action::make('revoke')
                    ->label('Revocă')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Cheia încetează să funcționeze imediat. Nu poate fi reactivată.')
                    ->visible(fn (ApiKey $record): bool => $record->revoked_at === null)
                    ->action(function (ApiKey $record): void {
                        $record->forceFill(['revoked_at' => now()])->save();

                        Notification::make()
                            ->title('Cheie revocată')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
        ];
    }
}
