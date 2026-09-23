<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerticalResource\Pages;
use App\Models\Vertical;
use App\Services\Content\ReservedSlugs;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class VerticalResource extends Resource
{
    protected static ?string $model = Vertical::class;

    protected static ?string $slug = 'domenii';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'domeniu';

    protected static ?string $pluralModelLabel = 'domenii';

    protected static ?string $navigationLabel = 'Domenii';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitate')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nume')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(120)
                            ->helperText('Apare direct în adresă: e-test.ro/slug')
                            // Domeniile se rezolvă în rădăcina site-ului, deci un slug
                            // egal cu o rută fixă ar produce o pagină inaccesibilă.
                            ->rules([
                                'alpha_dash:ascii',
                                Rule::notIn(ReservedSlugs::all()),
                            ])
                            ->unique(ignoreRecord: true),

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
                    ->description('Lăsate goale, se generează din nume și descriere.')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Titlu SEO')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('seo_description')
                            ->label('Descriere SEO')
                            ->rows(2)
                            ->maxLength(500),
                    ]),

                Section::make('Identitate vizuală')
                    ->description('Determină culoarea și pictograma domeniului pe site.')
                    ->schema([
                        Forms\Components\Select::make('metadata.accent.palette')
                            ->label('Accent')
                            ->options([
                                'auto' => 'Rugină (auto)',
                                'medical' => 'Verde clinic (medical)',
                                'legal' => 'Bordo (juridic)',
                                'it' => 'Albastru (IT)',
                                'lang' => 'Ocru (limbi)',
                            ])
                            ->placeholder('Ales automat'),

                        Forms\Components\Select::make('metadata.icon')
                            ->label('Pictogramă')
                            ->options([
                                'wheel' => 'Volan',
                                'pulse' => 'Puls',
                                'scales' => 'Balanță',
                                'terminal' => 'Terminal',
                                'globe' => 'Glob',
                                'book' => 'Carte',
                            ])
                            ->placeholder('Carte'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('tests_count')
                    ->label('Teste')
                    ->counts('tests')
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Întrebări')
                    ->counts('questions')
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
            'index' => Pages\ListVerticals::route('/'),
            'create' => Pages\CreateVertical::route('/create'),
            'edit' => Pages\EditVertical::route('/{record}/edit'),
        ];
    }
}
