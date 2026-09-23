<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $slug = 'marca-proprie';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Platformă';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'partener';

    protected static ?string $pluralModelLabel = 'parteneri cu marcă proprie';

    protected static ?string $navigationLabel = 'Marcă proprie';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partener')
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
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activ')
                            ->default(true)
                            ->helperText('Dezactivat, domeniile lui răspund 404.'),
                    ])
                    ->columns(3),

                Section::make('Domenii')
                    ->description('Gazda cu care ajunge vizitatorul decide partenerul. Fără niciun domeniu, partenerul nu e accesibil.')
                    ->schema([
                        Forms\Components\Repeater::make('domains')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('host')
                                    ->label('Gazdă')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('teste.partener.ro')
                                    ->helperText('Doar gazda, fără https:// și fără cale.')
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Principal'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activ')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->addActionLabel('Adaugă domeniu')
                            ->columnSpanFull(),
                    ]),

                Section::make('Domenii accesibile')
                    ->description('Partenerul vede doar verticalele bifate. Nimic bifat înseamnă acces la tot.')
                    ->schema([
                        Forms\Components\Select::make('verticals')
                            ->label('Verticale')
                            ->relationship('verticals', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Identitate vizuală')
                    ->description('Suprascrie numele, culoarea și textele pe domeniile partenerului.')
                    ->schema([
                        Forms\Components\KeyValue::make('branding')
                            ->label('')
                            ->keyLabel('Cheie')
                            ->valueLabel('Valoare')
                            ->addActionLabel('Adaugă setare')
                            ->helperText('Chei recunoscute: site_name, primary_color, logo_url, favicon_url, footer_text, tagline, seo_title, seo_description.'),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('domains_count')
                    ->label('Domenii')
                    ->counts('domains'),

                Tables\Columns\TextColumn::make('verticals_count')
                    ->label('Verticale')
                    ->counts('verticals'),

                Tables\Columns\TextColumn::make('apiClients_count')
                    ->label('Clienți API')
                    ->counts('apiClients'),

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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
