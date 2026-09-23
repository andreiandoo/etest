<?php

namespace App\Filament\Widgets;

use App\Enums\PublicationStatus;
use App\Filament\Resources\QuestionResource;
use App\Filament\Support\EditorialActions;
use App\Models\Question;
use Filament\Actions;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Întrebările care așteaptă decizia de publicare.
 *
 * Coloana cu sursa e pusă lângă enunț intenționat: la revizuire, prima
 * întrebare e „de unde știm asta”, iar o întrebare fără sursă citată nu ar
 * trebui publicată fără ca omul să vadă asta din listă.
 */
class QuestionsAwaitingReview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Întrebări în revizuire';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Question::query()
                    ->with(['vertical', 'taxonomyNode'])
                    ->where('status', PublicationStatus::Review->value)
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('prompt')
                    ->label('Enunț')
                    ->wrap()
                    ->limit(120)
                    ->searchable()
                    ->description(function (Question $record): string {
                        $node = $record->taxonomyNode;

                        return $node === null ? $record->vertical->name : $record->vertical->name.' · '.$node->name;
                    }),

                TextColumn::make('source_label')
                    ->label('Sursă')
                    ->limit(40)
                    ->placeholder('lipsește')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null || $state === '' ? 'danger' : 'gray'),

                TextColumn::make('difficulty')
                    ->label('Dificultate')
                    ->placeholder('—'),

                TextColumn::make('updated_at')
                    ->label('Modificată')
                    ->since()
                    ->dateTimeTooltip('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vertical')
                    ->label('Domeniu')
                    ->relationship('vertical', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('open')
                    ->label('Deschide')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->url(fn (Question $record): string => QuestionResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                EditorialActions::publish(),
                EditorialActions::returnToDraft(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    EditorialActions::publishBulk(),
                ]),
            ])
            ->emptyStateHeading('Nicio întrebare în revizuire')
            ->emptyStateDescription('Coada e goală. Ce e în lucru apare la Întrebări, în fila „Ciorne”.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
