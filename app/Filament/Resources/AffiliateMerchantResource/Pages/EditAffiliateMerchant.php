<?php

namespace App\Filament\Resources\AffiliateMerchantResource\Pages;

use App\Filament\Resources\AffiliateMerchantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateMerchant extends EditRecord
{
    protected static string $resource = AffiliateMerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
