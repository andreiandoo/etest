<?php

namespace App\Filament\Resources\AffiliateResourceResource\Pages;

use App\Filament\Resources\AffiliateResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateResource extends EditRecord
{
    protected static string $resource = AffiliateResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
