<?php

namespace App\Filament\Resources\AffiliateResourceResource\Pages;

use App\Filament\Resources\AffiliateResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateResources extends ListRecords
{
    protected static string $resource = AffiliateResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Resursă nouă'),
        ];
    }
}
