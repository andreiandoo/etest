<?php

namespace App\Filament\Widgets;

use App\Enums\PublicationStatus;
use App\Filament\Resources\TestResource;
use App\Filament\Support\EditorialActions;
use App\Models\TestDefinition;
use Filament\Actions;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Testele care așteaptă decizia de publicare.
 *
 * Numărul de întrebări e afișat pentru că un test în revizuire cu zero
 * întrebări e cel mai ușor de publicat din greșeală și cel mai vizibil defect
 * pentru vizitator.
 */
class TestsAwaitingReview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Teste în revizuire';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TestDefinition::query()
                    ->with(['vertical', 'taxonomyNode'])
                    ->withCount('questions')
                    ->where('status', PublicationStatus::Review->value)
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Test')
                    ->searchable()
                    ->wrap()
                    ->description(function (TestDefinition $record): string {
                        $node = $record->taxonomyNode;

                        return $node === null ? $record->vertical->name : $record->vertical->name.' · '.$node->name;
                    }),

                TextColumn::make('questions_count')
                    ->label('Întrebări')
                    ->badge()
                    ->color(fn (?string $state): string => (int) $state === 0 ? 'danger' : 'gray'),

                TextColumn::make('mode')
                    ->label('Mod')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => TestResource::modeOptions()[is_object($state) ? $state->value : $state] ?? '—'),

                TextColumn::make('updated_at')
                    ->label('Modificat')
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
                    ->url(fn (TestDefinition $record): string => TestResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                EditorialActions::publish(),
                EditorialActions::returnToDraft(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    EditorialActions::publishBulk(),
                ]),
            ])
            ->emptyStateHeading('Niciun test în revizuire')
            ->emptyStateDescription('Coada e goală. Ce e în lucru apare la Teste, în fila „Ciorne”.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }
}
