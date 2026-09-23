<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateMerchantResource\Pages;
use App\Models\AffiliateMerchant;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AffiliateMerchantResource extends Resource
{
    protected static ?string $model = AffiliateMerchant::class;

    protected static ?string $slug = 'comercianti-afiliati';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'comerciant';

    protected static ?string $pluralModelLabel = 'comercianți afiliați';

    protected static ?string $navigationLabel = 'Comercianți afiliați';

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
                    ->maxLength(2048)
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('resources_count')
                    ->label('Resurse')
                    ->counts('resources'),

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
            'index' => Pages\ListAffiliateMerchants::route('/'),
            'create' => Pages\CreateAffiliateMerchant::route('/create'),
            'edit' => Pages\EditAffiliateMerchant::route('/{record}/edit'),
        ];
    }
}
