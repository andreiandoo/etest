<?php

namespace App\Filament\Resources;

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
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

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $slug = 'intrebari';

    protected static ?string $recordTitleAttribute = 'prompt';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'întrebare';

    protected static ?string $pluralModelLabel = 'întrebări';

    protected static ?string $navigationLabel = 'Întrebări';

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            QuestionType::SingleChoice->value => 'Un singur răspuns',
            QuestionType::MultipleChoice->value => 'Răspunsuri multiple',
            QuestionType::TrueFalse->value => 'Adevărat / fals',
            QuestionType::Numeric->value => 'Numeric',
            QuestionType::ShortText->value => 'Text scurt',
            QuestionType::Matching->value => 'Corespondențe',
            QuestionType::Ordering->value => 'Ordonare',
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

    /**
     * Tipurile ale căror variante se editează prin lista de opțiuni.
     *
     * @return array<int, string>
     */
    private static function choiceTypes(): array
    {
        return [
            QuestionType::SingleChoice->value,
            QuestionType::MultipleChoice->value,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Întrebarea')
                    ->schema([
                        Forms\Components\Textarea::make('prompt')
                            ->label('Enunț')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tip')
                            ->options(self::typeOptions())
                            ->required()
                            ->default(QuestionType::SingleChoice->value)
                            ->live(),

                        Forms\Components\TextInput::make('difficulty')
                            ->label('Dificultate (1–5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(3),
                    ])
                    ->columns(2),

                Section::make('Variante de răspuns')
                    ->description('Bifează varianta corectă. Ordinea de aici e cea implicită; testele o pot amesteca.')
                    ->visible(fn (Get $get): bool => in_array($get('type'), self::choiceTypes(), true))
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Textarea::make('content')
                                    ->label('Text')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpan(3),

                                Forms\Components\Toggle::make('is_correct')
                                    ->label('Corect')
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->orderColumn('position')
                            ->defaultItems(3)
                            ->minItems(2)
                            ->addActionLabel('Adaugă variantă')
                            ->columnSpanFull(),
                    ]),

                Section::make('Explicație și sursă')
                    ->description('Sursa nu e opțională: fiecare răspuns publicat trebuie să arate de unde vine.')
                    ->schema([
                        Forms\Components\Textarea::make('explanation')
                            ->label('Explicație')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('source_label')
                            ->label('Sursa citată')
                            ->maxLength(255)
                            ->placeholder('ex. OUG 195/2002, art. 142'),

                        Forms\Components\TextInput::make('source_url')
                            ->label('Link către sursă')
                            ->url()
                            ->maxLength(2048),

                        Forms\Components\DatePicker::make('source_checked_at')
                            ->label('Verificat ultima dată la')
                            ->displayFormat('d.m.Y'),
                    ])
                    ->columns(2),

                Section::make('Clasificare și publicare')
                    ->schema([
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
                            ->placeholder('Fără secțiune'),

                        Forms\Components\Select::make('status')
                            ->label('Stare')
                            ->options(self::statusOptions())
                            ->required()
                            ->default(PublicationStatus::Draft->value),

                        Forms\Components\TextInput::make('source_key')
                            ->label('Cheie sursă')
                            ->maxLength(255)
                            ->helperText('Identificatorul din documentul original. Ține importurile idempotente.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prompt')
                    ->label('Enunț')
                    ->searchable()
                    ->wrap()
                    ->limit(90),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->sortable(),

                Tables\Columns\TextColumn::make('taxonomyNode.name')
                    ->label('Secțiune')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => self::typeOptions()[is_object($state) ? $state->value : $state] ?? '—'),

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

                Tables\Columns\TextColumn::make('source_label')
                    ->label('Sursă')
                    ->limit(40)
                    ->placeholder('lipsește')
                    ->color(fn (?string $state): string => $state === null || $state === '' ? 'danger' : 'gray'),
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

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options(self::typeOptions()),

                Tables\Filters\Filter::make('fara_sursa')
                    ->label('Fără sursă citată')
                    ->query(fn (Builder $query): Builder => $query->whereNull('source_label')->orWhere('source_label', '')),
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
