<?php

namespace App\Filament\Widgets;

use App\Models\TestAttempt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Ultimele încercări. Arată dacă platforma e efectiv folosită și pe ce.
 */
class RecentAttempts extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Ultimele teste începute';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TestAttempt::query()
                    ->with(['test.vertical', 'user'])
                    ->latest('created_at')
                    ->limit(10)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('test.title')
                    ->label('Test')
                    ->description(fn (TestAttempt $record): string => $record->test->vertical->name),

                TextColumn::make('user.name')
                    ->label('Utilizator')
                    ->default('—'),

                TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ((string) (is_object($state) ? $state->value : $state)) {
                        'completed' => 'finalizat',
                        'in_progress' => 'în desfășurare',
                        'expired' => 'expirat',
                        default => (string) (is_object($state) ? $state->value : $state),
                    })
                    ->color(fn ($state): string => match ((string) (is_object($state) ? $state->value : $state)) {
                        'completed' => 'success',
                        'expired' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('percentage')
                    ->label('Scor')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : number_format((float) $state, 1, ',', ' ').'%'),

                TextColumn::make('created_at')
                    ->label('Început')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ]);
    }
}
