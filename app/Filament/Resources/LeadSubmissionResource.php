<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadSubmissionResource\Pages;
use App\Models\LeadSubmission;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Solicitările trimise de utilizatori.
 *
 * Sunt date personale date cu consimțământ explicit pentru un scop anume, deci
 * ecranul e strict de citire și export: nu se editează conținutul a ceea ce a
 * scris o persoană despre ea însăși.
 */
class LeadSubmissionResource extends Resource
{
    protected static ?string $model = LeadSubmission::class;

    protected static ?string $slug = 'lead-uri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'solicitare';

    protected static ?string $pluralModelLabel = 'solicitări';

    protected static ?string $navigationLabel = 'Solicitări primite';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Primită')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campanie')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stare')
                    ->badge(),

                Tables\Columns\TextColumn::make('consented_at')
                    ->label('Consimțământ')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('lead_campaign_id')
                    ->label('Campanie')
                    ->relationship('campaign', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\DeleteAction::make()
                    ->label('Șterge')
                    ->modalDescription('Ștergerea e definitivă. Folosește-o pentru cereri de ștergere a datelor.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeadSubmissions::route('/'),
        ];
    }
}
