<?php

namespace App\Filament\Resources\LeadCampaignResource\Pages;

use App\Filament\Resources\LeadCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadCampaigns extends ListRecords
{
    protected static string $resource = LeadCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Campanie nouă'),
        ];
    }
}
