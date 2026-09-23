<?php

namespace App\Filament\Resources\TestResource\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\TestResource;
use App\Models\TestDefinition;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTests extends ListRecords
{
    protected static string $resource = TestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Test nou'),
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
                ->badge(fn (): int => TestDefinition::query()->where('status', PublicationStatus::Review->value)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Review->value)),

            'publicate' => Tab::make('Publicate')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Published->value)),

            'arhivate' => Tab::make('Arhivate')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PublicationStatus::Archived->value)),
        ];
    }
}
