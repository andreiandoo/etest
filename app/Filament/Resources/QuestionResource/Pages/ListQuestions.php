<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\QuestionResource;
use App\Models\Question;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filele sunt cozi de lucru, nu doar filtre.
 *
 * „Fără sursă” și „Surse expirate” există pentru că o întrebare de examen fără
 * o sursă citată și verificată recent e un risc editorial, nu o scăpare
 * cosmetică. Insignele arată mărimea problemei fără să fie nevoie să intri.
 */
class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Întrebare nouă'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'toate' => Tab::make('Toate'),

            'ciorne' => Tab::make('Ciorne')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Draft->value)),

            'revizuire' => Tab::make('În revizuire')
                ->badge(fn (): int => Question::query()->where('status', PublicationStatus::Review->value)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Review->value)),

            'publicate' => Tab::make('Publicate')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Published->value)),

            'fara-sursa' => Tab::make('Fără sursă')
                ->badge(fn (): int => Question::query()->whereNull('source_url')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('source_url')),

            'surse-expirate' => Tab::make('Surse expirate')
                ->badge(fn (): int => self::staleSources(Question::query())->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::staleSources($query)->orderBy('source_checked_at')),
        ];
    }

    /**
     * Sursă citată, dar neverificată de peste șase luni — sau niciodată.
     *
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    private static function staleSources(Builder $query): Builder
    {
        return $query
            ->whereNotNull('source_url')
            ->where(fn (Builder $inner): Builder => $inner
                ->whereNull('source_checked_at')
                ->orWhere('source_checked_at', '<', today()->subMonths(6)));
    }
}
