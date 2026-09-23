<?php

namespace App\Filament\Resources\VerticalResource\Pages;

use App\Filament\Resources\VerticalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateVertical extends CreateRecord
{
    protected static string $resource = VerticalResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['slug'] ?? null)) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? ''));
        }

        return $data;
    }
}
