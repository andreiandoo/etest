<?php

namespace App\Filament\Resources\SponsorCampaignResource\Pages;

use App\Filament\Resources\SponsorCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSponsorCampaign extends EditRecord
{
    protected static string $resource = SponsorCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
