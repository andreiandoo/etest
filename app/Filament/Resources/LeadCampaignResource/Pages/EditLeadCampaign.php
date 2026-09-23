<?php

namespace App\Filament\Resources\LeadCampaignResource\Pages;

use App\Filament\Resources\LeadCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeadCampaign extends EditRecord
{
    protected static string $resource = LeadCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
