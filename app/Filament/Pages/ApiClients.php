<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApiClients extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'api';

    protected static ?string $navigationLabel = 'API';

    protected static ?string $title = 'API';

    protected static string | UnitEnum | null $navigationGroup = 'Platformă';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 20;

    protected static function legacyComponent(): string
    {
        return 'admin.api-manager';
    }
}
