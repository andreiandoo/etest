<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SponsorCampaignResource\Pages;
use App\Models\SponsorCampaign;
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

class SponsorCampaignResource extends Resource
{
    protected static ?string $model = SponsorCampaign::class;

    protected static ?string $slug = 'campanii-sponsorizate';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'campanie';

    protected static ?string $pluralModelLabel = 'campanii sponsorizate';

    protected static ?string $navigationLabel = 'Campanii sponsorizate';

    /**
     * @return array<string, string>
     */
    public static function placementOptions(): array
    {
        return [
            'content' => 'În pagină',
            'sidebar' => 'Lateral',
            'header' => 'Sus',
            'footer' => 'Jos',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mesajul')
                    ->schema([
                        Forms\Components\Select::make('sponsor_id')
                            ->label('Sponsor')
                            ->relationship('sponsor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nume intern')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nu se afișează public.'),

                        Forms\Components\TextInput::make('headline')
                            ->label('Titlu afișat')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body')
                            ->label('Text')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cta_label')
                            ->label('Text buton')
                            ->required()
                            ->maxLength(120),

                        Forms\Components\TextInput::make('cta_url')
                            ->label('Link buton')
                            ->url()
                            ->required()
                            ->maxLength(2048),

                        Forms\Components\TextInput::make('disclosure_label')
                            ->label('Etichetă de transparență')
                            ->required()
                            ->maxLength(120)
                            ->default('Conținut sponsorizat')
                            ->helperText('Apare deasupra mesajului. Conținutul plătit se declară întotdeauna.')
                            ->columnSpanFull(),
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
                            ->default(0)
                            ->helperText('Mai mare câștigă când se potrivesc mai multe.'),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Începe la')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Se termină la')
                            ->seconds(false),
                    ])
                    ->columns(2),

                Section::make('Unde apare')
                    ->description('Fără nicio poziție, campania nu se afișează nicăieri.')
                    ->schema([
                        Forms\Components\Repeater::make('placements')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('placement')
                                    ->label('Poziție')
                                    ->options(self::placementOptions())
                                    ->default('content')
                                    ->required(),

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

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activă')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->addActionLabel('Adaugă poziție')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campanie')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SponsorCampaign $record): string => $record->headline),

                Tables\Columns\TextColumn::make('sponsor.name')
                    ->label('Sponsor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('placements_count')
                    ->label('Poziții')
                    ->counts('placements'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritate')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activă')
                    ->boolean(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Până la')
                    ->dateTime('d.m.Y')
                    ->placeholder('fără termen'),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sponsor')
                    ->label('Sponsor')
                    ->relationship('sponsor', 'name')
                    ->searchable()
                    ->preload(),

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
            'index' => Pages\ListSponsorCampaigns::route('/'),
            'create' => Pages\CreateSponsorCampaign::route('/create'),
            'edit' => Pages\EditSponsorCampaign::route('/{record}/edit'),
        ];
    }
}
