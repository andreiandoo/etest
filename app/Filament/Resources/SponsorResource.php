<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SponsorResource\Pages;
use App\Models\Sponsor;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SponsorResource extends Resource
{
    protected static ?string $model = Sponsor::class;

    protected static ?string $slug = 'sponsori';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'sponsor';

    protected static ?string $pluralModelLabel = 'sponsori';

    protected static ?string $navigationLabel = 'Sponsori';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nume')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activ')
                    ->default(true),

                Forms\Components\TextInput::make('website_url')
                    ->label('Site')
                    ->url()
                    ->maxLength(2048),

                Forms\Components\TextInput::make('logo_url')
                    ->label('Logo (URL)')
                    ->url()
                    ->maxLength(2048),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('campaigns_count')
                    ->label('Campanii')
                    ->counts('campaigns'),

                Tables\Columns\TextColumn::make('website_url')
                    ->label('Site')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Activ'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSponsors::route('/'),
            'create' => Pages\CreateSponsor::route('/create'),
            'edit' => Pages\EditSponsor::route('/{record}/edit'),
        ];
    }
}
