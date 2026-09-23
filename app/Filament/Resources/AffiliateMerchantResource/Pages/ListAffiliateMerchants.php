<?php

namespace App\Filament\Resources\AffiliateMerchantResource\Pages;

use App\Filament\Resources\AffiliateMerchantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateMerchants extends ListRecords
{
    protected static string $resource = AffiliateMerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Comerciant nou'),
        ];
    }
}
