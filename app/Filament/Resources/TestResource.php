<?php

namespace App\Filament\Resources;

use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use App\Filament\Resources\TestResource\Pages;
use App\Filament\Support\EditorialActions;
use App\Models\TestDefinition;
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

class TestResource extends Resource
{
    protected static ?string $model = TestDefinition::class;

    protected static ?string $slug = 'teste';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'test';

    protected static ?string $pluralModelLabel = 'teste';

    protected static ?string $navigationLabel = 'Teste';

    /**
     * @return array<string, string>
     */
    public static function modeOptions(): array
    {
        return [
            TestMode::Practice->value => 'Exersare',
            TestMode::Exam->value => 'Examen',
            TestMode::Quick->value => 'Rapid',
            TestMode::Daily->value => 'Zilnic',
            TestMode::Adaptive->value => 'Adaptiv',
            TestMode::Custom->value => 'Personalizat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            PublicationStatus::Draft->value => 'Ciornă',
            PublicationStatus::Review->value => 'În revizuire',
            PublicationStatus::Published->value => 'Publicat',
            PublicationStatus::Archived->value => 'Arhivat',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ce test este')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titlu')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash:ascii']),

                        Forms\Components\Select::make('vertical_id')
                            ->label('Domeniu')
                            ->relationship('vertical', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('taxonomy_node_id')
                            ->label('Secțiune')
                            ->relationship(
                                'taxonomyNode',
                                'name',
                                fn (Builder $query, Get $get) => $query->where('vertical_id', $get('vertical_id')),
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Direct sub domeniu'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descriere')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('instructions')
                            ->label('Instrucțiuni afișate înainte de start')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Cum se desfășoară')
                    ->schema([
                        Forms\Components\Select::make('mode')
                            ->label('Mod')
                            ->options(self::modeOptions())
                            ->required()
                            ->default(TestMode::Practice->value),

                        Forms\Components\TextInput::make('question_limit')
                            ->label('Număr de întrebări')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('Gol înseamnă toate întrebările atașate.'),

                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Durată (secunde)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Gol înseamnă fără limită de timp.'),

                        Forms\Components\TextInput::make('passing_percentage')
                            ->label('Prag de promovare (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\Toggle::make('randomize_questions')
                            ->label('Amestecă întrebările'),

                        Forms\Components\Toggle::make('randomize_options')
                            ->label('Amestecă variantele'),

                        Forms\Components\Toggle::make('allow_review')
                            ->label('Permite revenirea la întrebări')
                            ->default(true),

                        Forms\Components\Toggle::make('show_explanations')
                            ->label('Arată explicația imediat')
                            ->default(true)
                            ->helperText('La mod examen se ține de obicei oprit.'),
                    ])
                    ->columns(2),

                Section::make('Publicare')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Stare')
                            ->options(self::statusOptions())
                            ->required()
                            ->default(PublicationStatus::Draft->value)
                            ->live(),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publicat la')
                            ->seconds(false)
                            ->helperText('Un test e vizibil public doar cu stare „Publicat” și o dată în trecut.'),
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
                Tables\Columns\TextColumn::make('title')
                    ->label('Titlu')
                    ->searchable()
                    ->sortable()
                    ->description(function (TestDefinition $record): string {
                        $node = $record->taxonomyNode;

                        return $node === null ? $record->vertical->name : $node->name;
                    }),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mode')
                    ->label('Mod')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => self::modeOptions()[is_object($state) ? $state->value : $state] ?? '—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => self::statusOptions()[is_object($state) ? $state->value : $state] ?? '—')
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : $state) {
                        'published' => 'success',
                        'review' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Întrebări')
                    ->counts('questions'),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->label('Încercări')
                    ->counts('attempts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicat')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Stare')
                    ->options(self::statusOptions()),

                Tables\Filters\SelectFilter::make('mode')
                    ->label('Mod')
                    ->options(self::modeOptions()),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\ActionGroup::make([
                    EditorialActions::submitForReview(),
                    EditorialActions::publish(),
                    EditorialActions::returnToDraft(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    EditorialActions::submitForReviewBulk(),
                    EditorialActions::publishBulk(),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}
