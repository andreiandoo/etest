<?php

namespace App\Filament\Resources\QuestionReportResource\Pages;

use App\Filament\Resources\QuestionReportResource;
use App\Models\QuestionReport;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Se deschide pe sesizările nerezolvate, nu pe toate.
 *
 * Ecranul e o coadă de lucru: dacă ar porni pe „toate”, cele câteva sesizări
 * deschise s-ar pierde între sutele închise cu luni în urmă.
 */
class ListQuestionReports extends ListRecords
{
    protected static string $resource = QuestionReportResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'deschise' => Tab::make('Deschise')
                ->badge(fn (): int => QuestionReport::query()->where('status', 'open')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'open')),

            'rezolvate' => Tab::make('Rezolvate')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'resolved')),

            'toate' => Tab::make('Toate'),
        ];
    }
}
