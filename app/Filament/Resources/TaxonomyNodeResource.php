<?php

namespace App\Filament\Resources;

use App\Enums\TaxonomyNodeType;
use App\Filament\Resources\TaxonomyNodeResource\Pages;
use App\Models\TaxonomyNode;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaxonomyNodeResource extends Resource
{
    protected static ?string $model = TaxonomyNode::class;

    protected static ?string $slug = 'taxonomie';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'secțiune';

    protected static ?string $pluralModelLabel = 'taxonomie';

    protected static ?string $navigationLabel = 'Taxonomie';

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            TaxonomyNodeType::Domain->value => 'Domeniu',
            TaxonomyNodeType::Exam->value => 'Examen',
            TaxonomyNodeType::Certification->value => 'Certificare',
            TaxonomyNodeType::Subject->value => 'Materie',
            TaxonomyNodeType::Chapter->value => 'Capitol',
            TaxonomyNodeType::Topic->value => 'Temă',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Locul în structură')
                    ->schema([
                        Forms\Components\Select::make('vertical_id')
                            ->label('Domeniu')
                            ->relationship('vertical', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('parent_id')
                            ->label('Părinte')
                            ->relationship(
                                'parent',
                                'name',
                                // Un nod nu poate fi propriul părinte, iar părintele
                                // trebuie să fie din același domeniu.
                                fn (Builder $query, ?TaxonomyNode $record, Forms\Get $get) => $query
                                    ->where('vertical_id', $get('vertical_id'))
                                    ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey())),
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Fără părinte — stă direct sub domeniu'),

                        Forms\Components\Select::make('type')
                            ->label('Tip')
                            ->options(self::typeOptions())
                            ->required()
                            ->default(TaxonomyNodeType::Exam->value),
                    ])
                    ->columns(3),

                Section::make('Identitate')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nume')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash:ascii'])
                            ->helperText('Apare în adresă, după domeniu.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descriere')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Publicare')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activ')
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordine')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(32767),
                    ])
                    ->columns(2),

                Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Titlu SEO')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('seo_description')
                            ->label('Descriere SEO')
                            ->rows(2)
                            ->maxLength(500),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->sortable()
                    ->description(fn (TaxonomyNode $record): ?string => $record->parent?->name),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('tests_count')
                    ->label('Teste')
                    ->counts('tests')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordine')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options(self::typeOptions()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activ'),
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
            'index' => Pages\ListTaxonomyNodes::route('/'),
            'create' => Pages\CreateTaxonomyNode::route('/create'),
            'edit' => Pages\EditTaxonomyNode::route('/{record}/edit'),
        ];
    }
}
