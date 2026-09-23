<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Tenants extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'marca-proprie';

    protected static ?string $navigationLabel = 'Marcă proprie';

    protected static ?string $title = 'Marcă proprie';

    protected static string | UnitEnum | null $navigationGroup = 'Platformă';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 10;

    protected static function legacyComponent(): string
    {
        return 'admin.tenant-manager';
    }
}
