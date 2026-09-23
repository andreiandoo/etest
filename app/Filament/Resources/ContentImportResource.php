<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentImportResource\Pages;
use App\Jobs\ProcessContentImport;
use App\Models\ContentImport;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Importuri de conținut.
 *
 * Încărcarea nu scrie nimic direct: fișierul e stocat, importul e înregistrat
 * ca „în așteptare” și prelucrarea se face într-un job din coadă. Un fișier de
 * câteva mii de rânduri nu are ce căuta într-un ciclu de request, iar
 * rezultatul — rânduri create, actualizate, respinse — rămâne vizibil aici.
 */
class ContentImportResource extends Resource
{
    protected static ?string $model = ContentImport::class;

    protected static ?string $slug = 'importuri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'import';

    protected static ?string $pluralModelLabel = 'importuri';

    protected static ?string $navigationLabel = 'Importuri';

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'pending' => 'În așteptare',
            'processing' => 'Se procesează',
            'completed' => 'Finalizat',
            'completed_with_errors' => 'Finalizat cu erori',
            'failed' => 'Eșuat',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fișier de importat')
                    ->description('Întrebările intră ca ciorne, indiferent de conținutul fișierului. Nimic nu ajunge pe site fără o revizuire.')
                    ->schema([
                        Forms\Components\Select::make('vertical_id')
                            ->label('Domeniu')
                            ->relationship('vertical', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Toate rândurile din fișier ajung în acest domeniu.'),

                        Forms\Components\FileUpload::make('stored_path')
                            ->label('Fișier')
                            ->disk('local')
                            ->directory('imports')
                            ->storeFileNamesIn('original_name')
                            ->required()
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/json',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(20 * 1024)
                            ->helperText('CSV, JSON, XLS sau XLSX, până la 20 MB. Coloana „source_key” decide ce se actualizează și ce se creează.')
                            ->columnSpanFull(),

                        // Fișierul e stocat sub un nume generat, ca două
                        // încărcări cu același nume să nu se suprascrie.
                        // Numele dat de om se păstrează separat, altfel lista
                        // de importuri ar fi un șir de identificatori.
                        Forms\Components\Hidden::make('original_name'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Fișier')
                    ->searchable()
                    ->description(fn (ContentImport $record): string => strtoupper((string) $record->format)),

                Tables\Columns\TextColumn::make('vertical.name')
                    ->label('Domeniu')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'completed_with_errors' => 'warning',
                        'processing' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('processed_rows')
                    ->label('Rânduri')
                    ->formatStateUsing(fn (ContentImport $record): string => sprintf(
                        '%d din %d',
                        (int) $record->processed_rows,
                        (int) $record->total_rows,
                    )),

                Tables\Columns\TextColumn::make('created_rows')
                    ->label('Create')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('updated_rows')
                    ->label('Actualizate')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('failed_rows')
                    ->label('Respinse')
                    ->badge()
                    ->color(fn (?string $state): string => (int) $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Încărcat de')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Încărcat')
                    ->since()
                    ->dateTimeTooltip('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('15s')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stare')
                    ->options(self::statusOptions()),

                Tables\Filters\SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('errors')
                    ->label('Vezi rândurile respinse')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
                    ->visible(fn (ContentImport $record): bool => (int) $record->failed_rows > 0 || $record->status === 'failed')
                    ->modalHeading('Rânduri respinse')
                    ->modalContent(fn (ContentImport $record) => view('filament.imports.errors', [
                        'errors' => $record->errors ?? [],
                    ]))
                    ->modalSubmitAction(false),

                Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Niciun import')
            ->emptyStateDescription('Încarcă un fișier CSV, JSON sau XLSX ca să aduci întrebări în lot.')
            ->emptyStateIcon(Heroicon::OutlinedArrowUpTray);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContentImports::route('/'),
            'create' => Pages\CreateContentImport::route('/create'),
        ];
    }

    /**
     * Completează ce nu poate veni din formular.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareImport(array $data): array
    {
        $storedPath = (string) $data['stored_path'];
        $originalName = trim((string) ($data['original_name'] ?? ''));

        $data['user_id'] = Auth::id();
        $data['type'] = 'questions';
        $data['status'] = 'pending';
        $data['original_name'] = $originalName !== '' ? $originalName : basename($storedPath);
        $data['format'] = strtolower(pathinfo($data['original_name'], PATHINFO_EXTENSION))
            ?: strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        return $data;
    }

    public static function dispatchImport(ContentImport $import): void
    {
        ProcessContentImport::dispatch($import->id);
    }
}
