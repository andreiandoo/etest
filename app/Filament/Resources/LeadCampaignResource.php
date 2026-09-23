<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadCampaignResource\Pages;
use App\Models\LeadCampaign;
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

class LeadCampaignResource extends Resource
{
    protected static ?string $model = LeadCampaign::class;

    protected static ?string $slug = 'campanii-lead';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'campanie de lead';

    protected static ?string $pluralModelLabel = 'campanii de lead';

    protected static ?string $navigationLabel = 'Campanii de lead';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formularul')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nume intern')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title')
                            ->label('Titlu afișat')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descriere')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cta_label')
                            ->label('Text buton')
                            ->required()
                            ->maxLength(120),

                        Forms\Components\CheckboxList::make('requested_fields')
                            ->label('Câmpuri cerute')
                            ->options([
                                'name' => 'Nume',
                                'phone' => 'Telefon',
                            ])
                            ->helperText('Emailul se cere întotdeauna. Cere doar ce folosești efectiv.'),

                        Forms\Components\Textarea::make('consent_text')
                            ->label('Textul consimțământului')
                            ->required()
                            ->rows(3)
                            ->helperText('Trebuie să spună cine primește datele și în ce scop.')
                            ->columnSpanFull(),
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
                    ->description(fn (LeadCampaign $record): string => $record->name),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->placeholder('oricare'),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Solicitări')
                    ->counts('submissions')
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
            'index' => Pages\ListLeadCampaigns::route('/'),
            'create' => Pages\CreateLeadCampaign::route('/create'),
            'edit' => Pages\EditLeadCampaign::route('/{record}/edit'),
        ];
    }
}
