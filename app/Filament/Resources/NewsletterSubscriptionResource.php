<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriptionResource\Pages;
use App\Models\NewsletterSubscription;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Abonările la newsletter.
 *
 * Strict de citire. O abonare se creează doar prin dublă confirmare din site,
 * iar starea se schimbă prin confirmare, dezabonare sau un eveniment primit de
 * la Brevo. Adăugarea manuală a unei adrese ar ocoli consimțământul.
 */
class NewsletterSubscriptionResource extends Resource
{
    protected static ?string $model = NewsletterSubscription::class;

    protected static ?string $slug = 'newsletter';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'abonare';

    protected static ?string $pluralModelLabel = 'abonări';

    protected static ?string $navigationLabel = 'Newsletter';

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'pending' => 'Așteaptă confirmarea',
            'active' => 'Activă',
            'unsubscribed' => 'Dezabonată',
            'bounced' => 'Adresă respinsă',
            'complained' => 'Marcat ca spam',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('interest_key')
                    ->label('Interes')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'complained', 'bounced' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Confirmată')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Înscrisă')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stare')
                    ->options(self::statusOptions()),

                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\DeleteAction::make()
                    ->label('Șterge')
                    ->modalDescription('Folosește-o pentru cereri de ștergere a datelor. Dezabonarea obișnuită se face din emailul primit.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscriptions::route('/'),
        ];
    }
}
