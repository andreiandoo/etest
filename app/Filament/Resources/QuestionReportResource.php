<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionReportResource\Pages;
use App\Models\Question;
use App\Models\QuestionReport;
use BackedEnum;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Sesizările trimise de utilizatori din timpul testului.
 *
 * E singurul canal prin care cineva care chiar dă testul ne spune că o
 * întrebare e greșită sau depășită. Coada trebuie golită, nu doar consultată,
 * de aceea numărul de sesizări deschise apare ca insignă în meniu.
 */
class QuestionReportResource extends Resource
{
    protected static ?string $model = QuestionReport::class;

    protected static ?string $slug = 'calitate';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'sesizare';

    protected static ?string $pluralModelLabel = 'sesizări';

    protected static ?string $navigationLabel = 'Calitate';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = QuestionReport::query()->where('status', 'open')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * @return array<string, string>
     */
    public static function reasonOptions(): array
    {
        return [
            'incorrect' => 'Răspuns sau explicație greșită',
            'outdated' => 'Informație depășită',
            'ambiguous' => 'Formulare ambiguă',
            'typo' => 'Greșeală de scriere',
            'other' => 'Altă problemă',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['question.vertical', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Primită')
                    ->since()
                    ->dateTimeTooltip('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('question.prompt')
                    ->label('Întrebare')
                    ->wrap()
                    ->limit(110)
                    ->placeholder('întrebare ștearsă')
                    ->description(function (QuestionReport $record): string {
                        $question = $record->question;

                        return $question === null ? '—' : $question->vertical->name;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motiv')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (string $state): string => self::reasonOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('message')
                    ->label('Ce spune utilizatorul')
                    ->wrap()
                    ->limit(110)
                    ->placeholder('fără detalii'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Sesizată de')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Rezolvată')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motiv')
                    ->options(self::reasonOptions()),
            ])
            ->actions([
                Actions\Action::make('editQuestion')
                    ->label('Deschide întrebarea')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->visible(fn (QuestionReport $record): bool => $record->question_id !== null)
                    ->url(fn (QuestionReport $record): string => QuestionResource::getUrl('edit', ['record' => $record->question_id]))
                    ->openUrlInNewTab(),

                Actions\Action::make('verifySource')
                    ->label('Sursă verificată azi')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('gray')
                    ->visible(fn (QuestionReport $record): bool => $record->question_id !== null)
                    ->requiresConfirmation()
                    ->modalDescription('Marchează întrebarea ca verificată azi. Folosește-o doar după ce chiar ai confirmat sursa.')
                    ->action(function (QuestionReport $record): void {
                        Question::query()->whereKey($record->question_id)->update([
                            'source_checked_at' => today(),
                            'updated_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Sursa a fost marcată ca verificată azi')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('resolve')
                    ->label('Rezolvă')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (QuestionReport $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->modalDescription('Sesizarea iese din coadă. Fă-o după ce ai corectat întrebarea sau ai constatat că nu e nimic de corectat.')
                    ->action(function (QuestionReport $record): void {
                        self::resolve($record);

                        Notification::make()
                            ->title('Sesizare rezolvată')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('resolveSelected')
                        ->label('Marchează rezolvate')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => self::resolveMany($records)),
                ]),
            ])
            ->emptyStateHeading('Nicio sesizare')
            ->emptyStateDescription('Aici ajung întrebările semnalate de utilizatori în timpul testelor.')
            ->emptyStateIcon(Heroicon::OutlinedFlag);
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    private static function resolveMany(Collection $records): void
    {
        $resolved = 0;

        foreach ($records as $record) {
            if ($record instanceof QuestionReport && $record->status === 'open') {
                self::resolve($record);
                $resolved++;
            }
        }

        Notification::make()
            ->title($resolved.' sesizări rezolvate')
            ->success()
            ->send();
    }

    private static function resolve(QuestionReport $report): void
    {
        $report->forceFill([
            'status' => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ])->save();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionReports::route('/'),
        ];
    }
}
