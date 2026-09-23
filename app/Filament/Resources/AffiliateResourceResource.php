<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateResourceResource\Pages;
use App\Models\AffiliateResource;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AffiliateResourceResource extends Resource
{
    protected static ?string $model = AffiliateResource::class;

    protected static ?string $slug = 'afiliere';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'resursă afiliată';

    protected static ?string $pluralModelLabel = 'resurse afiliate';

    protected static ?string $navigationLabel = 'Resurse afiliate';

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'book' => 'Carte',
            'course' => 'Curs',
            'tool' => 'Instrument',
            'service' => 'Serviciu',
            'other' => 'Altceva',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resursa')
                    ->schema([
                        Forms\Components\Select::make('affiliate_merchant_id')
                            ->label('Comerciant')
                            ->relationship('merchant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('resource_type')
                            ->label('Tip')
                            ->options(self::typeOptions())
                            ->default('book')
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Titlu')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descriere')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('affiliate_url')
                            ->label('Link de afiliere')
                            ->url()
                            ->required()
                            ->maxLength(2048)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('image_url')
                            ->label('Imagine (URL)')
                            ->url()
                            ->maxLength(2048),

                        Forms\Components\TextInput::make('price_label')
                            ->label('Preț afișat')
                            ->maxLength(120)
                            ->placeholder('ex. de la 89 lei'),
                    ])
                    ->columns(2),

                Section::make('Unde apare')
                    ->schema([
                        Forms\Components\Select::make('vertical_id')
                            ->label('Domeniu')
                            ->relationship('vertical', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Oricare'),

                        Forms\Components\Select::make('taxonomy_node_id')
                            ->label('Secțiune')
                            ->relationship(
                                'taxonomyNode',
                                'name',
                                fn (Builder $query, Get $get) => $query->when(
                                    $get('vertical_id'),
                                    fn (Builder $q, $verticalId) => $q->where('vertical_id', $verticalId),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Oricare'),
                    ])
                    ->columns(2),

                Section::make('Când rulează')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activă')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->label('Prioritate')
                            ->numeric()
                            ->default(0),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Începe la')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Se termină la')
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titlu')
                    ->searchable()
                    ->sortable()
                    ->description(fn (AffiliateResource $record): string => $record->merchant->name),

                Tables\Columns\TextColumn::make('resource_type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->placeholder('oricare'),

                Tables\Columns\TextColumn::make('price_label')
                    ->label('Preț')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activă')
                    ->boolean(),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('merchant')
                    ->label('Comerciant')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('resource_type')
                    ->label('Tip')
                    ->options(self::typeOptions()),

                Tables\Filters\TernaryFilter::make('is_active')->label('Activă'),
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
            'index' => Pages\ListAffiliateResources::route('/'),
            'create' => Pages\CreateAffiliateResource::route('/create'),
            'edit' => Pages\EditAffiliateResource::route('/{record}/edit'),
        ];
    }
}
