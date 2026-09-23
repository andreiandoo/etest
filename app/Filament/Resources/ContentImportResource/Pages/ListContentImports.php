<?php

namespace App\Filament\Resources\ContentImportResource\Pages;

use App\Filament\Resources\ContentImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentImports extends ListRecords
{
    protected static string $resource = ContentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Import nou'),
        ];
    }
}
