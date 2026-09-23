<?php

namespace App\Filament\Resources\ContentImportResource\Pages;

use App\Filament\Resources\ContentImportResource;
use App\Models\ContentImport;
use Filament\Resources\Pages\CreateRecord;

class CreateContentImport extends CreateRecord
{
    protected static string $resource = ContentImportResource::class;

    protected static ?string $title = 'Import nou';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ContentImportResource::prepareImport($data);
    }

    /**
     * Prelucrarea se face în coadă, nu în request: un fișier de mii de rânduri
     * ar depăși orice limită rezonabilă de execuție.
     */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record instanceof ContentImport) {
            ContentImportResource::dispatchImport($record);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
