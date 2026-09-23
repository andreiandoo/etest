<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Models\Source;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Registrul surselor oficiale.
 *
 * Nu se editează din panou: un rând de aici e scris de conectorul lui, la
 * fiecare sincronizare. Dacă adresa documentului sau statutul drepturilor se
 * schimbă, se schimbă în conector și se resincronizează — altfel registrul ar
 * ajunge să spună una și importul să facă alta.
 *
 * Rostul ecranului e să răspundă la o singură întrebare, oricând: întrebarea
 * asta de unde vine, din ce fișier, din ce versiune și cu ce drept de folosire.
 */
class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static ?string $slug = 'surse';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'sursă';

    protected static ?string $pluralModelLabel = 'surse';

    protected static ?string $navigationLabel = 'Surse';

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    public static function rightsOptions(): array
    {
        return [
            'explicit_allowed' => 'Reutilizare permisă explicit',
            'permission_received' => 'Acord primit',
            'official_public_unclear' => 'Public oficial, drepturi neclare',
            'restricted' => 'Restricție explicită',
            'original_content' => 'Conținut original e-test',
            'research_only' => 'Doar pentru cercetare',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reviewOptions(): array
    {
        return [
            'discovered' => 'Identificată',
            'downloaded' => 'Descărcată',
            'parsed' => 'Parsată',
            'validated' => 'Validată',
            'rights_cleared' => 'Drepturi lămurite',
            'publishable' => 'Publicabilă',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('authority')
                    ->label('Instituție')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Source $record): string => $record->title),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('version_label')
                    ->label('Versiune')
                    ->placeholder('fără versiune')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('rights_status')
                    ->label('Drepturi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::rightsOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'explicit_allowed', 'permission_received' => 'success',
                        'restricted' => 'danger',
                        'original_content' => 'info',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('review_status')
                    ->label('Stadiu')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::reviewOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'publishable' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('item_count_raw')
                    ->label('Itemi')
                    ->placeholder('nenumărați')
                    ->sortable(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Versiuni descărcate')
                    ->counts('documents'),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Ultima sincronizare')
                    ->since()
                    ->dateTimeTooltip('d.m.Y H:i')
                    ->placeholder('niciodată')
                    ->sortable(),
            ])
            ->defaultSort('authority')
            ->filters([
                Tables\Filters\SelectFilter::make('rights_status')
                    ->label('Drepturi')
                    ->options(self::rightsOptions()),

                Tables\Filters\SelectFilter::make('review_status')
                    ->label('Stadiu')
                    ->options(self::reviewOptions()),

                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('document')
                    ->label('Documentul oficial')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Source $record): string => $record->document_url)
                    ->openUrlInNewTab(),

                Actions\Action::make('license')
                    ->label('Condiții de reutilizare')
                    ->icon(Heroicon::OutlinedScale)
                    ->color('gray')
                    ->visible(fn (Source $record): bool => $record->license_url !== null)
                    ->url(fn (Source $record): string => (string) $record->license_url)
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Niciun registru încă')
            ->emptyStateDescription('Sursele apar după prima rulare a comenzii sources:sync.')
            ->emptyStateIcon(Heroicon::OutlinedBookOpen);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSources::route('/'),
        ];
    }
}
