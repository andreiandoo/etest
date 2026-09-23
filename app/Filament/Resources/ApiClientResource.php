<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiClientResource\Pages;
use App\Models\ApiClient;
use App\Services\Api\ApiKeyIssuer;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

class ApiClientResource extends Resource
{
    protected static ?string $model = ApiClient::class;

    protected static ?string $slug = 'api';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Platformă';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'client API';

    protected static ?string $pluralModelLabel = 'clienți API';

    protected static ?string $navigationLabel = 'API';

    /**
     * @return array<string, string>
     */
    public static function scopeOptions(): array
    {
        return [
            'catalog:read' => 'Citește catalogul',
            'tests:read' => 'Citește testele',
            'questions:read' => 'Citește întrebările',
            'answers:read' => 'Citește răspunsurile corecte',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nume')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('contact_email')
                    ->label('Email de contact')
                    ->email()
                    ->maxLength(255),

                Forms\Components\Select::make('tenant_id')
                    ->label('Partener')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Fără partener — acces la tot catalogul')
                    ->helperText('Legat de un partener, clientul vede doar verticalele acestuia.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activ')
                    ->default(true)
                    ->helperText('Dezactivat, toate cheile lui sunt respinse.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Partener')
                    ->placeholder('tot catalogul'),

                Tables\Columns\TextColumn::make('keys_count')
                    ->label('Chei')
                    ->counts('keys'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Activ'),
            ])
            ->actions([
                self::issueKeyAction(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Emiterea unei chei.
     *
     * Cheia în clar există o singură dată, în răspunsul acestei acțiuni: în
     * baza de date se păstrează doar amprenta. Dacă e pierdută, nu poate fi
     * recuperată, ci doar revocată și emisă din nou — de aceea mesajul o spune
     * explicit, în loc să lase omul să presupună că o va regăsi mai târziu.
     */
    private static function issueKeyAction(): Actions\Action
    {
        return Actions\Action::make('issueKey')
            ->label('Emite cheie')
            ->icon(Heroicon::OutlinedKey)
            ->color('primary')
            ->visible(fn (ApiClient $record): bool => $record->is_active)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nume cheie')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('ex. integrare portal partener'),

                Forms\Components\CheckboxList::make('scopes')
                    ->label('Permisiuni')
                    ->options(self::scopeOptions())
                    ->required()
                    ->helperText('Acordă strict ce e nevoie. „Citește răspunsurile corecte” se dă doar cu motiv întemeiat.'),

                Forms\Components\TextInput::make('daily_quota')
                    ->label('Cotă zilnică')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('nelimitat'),

                Forms\Components\TextInput::make('monthly_quota')
                    ->label('Cotă lunară')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('nelimitat'),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiră la')
                    ->seconds(false)
                    ->placeholder('fără expirare'),
            ])
            ->action(function (ApiClient $record, array $data): void {
                try {
                    $issued = app(ApiKeyIssuer::class)->issue(
                        $record,
                        (string) $data['name'],
                        array_values((array) $data['scopes']),
                        $data['daily_quota'] === null ? null : (int) $data['daily_quota'],
                        $data['monthly_quota'] === null ? null : (int) $data['monthly_quota'],
                        $data['expires_at'] === null ? null : new \DateTimeImmutable((string) $data['expires_at']),
                    );
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Cheia nu a putut fi emisă')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Cheie emisă — copiaz-o acum')
                    ->body($issued['plain_text'].' — nu mai poate fi afișată. Dacă o pierzi, revoc-o și emite alta.')
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiClients::route('/'),
            'create' => Pages\CreateApiClient::route('/create'),
            'edit' => Pages\EditApiClient::route('/{record}/edit'),
        ];
    }
}
