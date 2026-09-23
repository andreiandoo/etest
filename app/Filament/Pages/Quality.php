<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Quality extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'calitate';

    protected static ?string $navigationLabel = 'Calitate';

    protected static ?string $title = 'Calitate';

    protected static string | UnitEnum | null $navigationGroup = 'Conținut';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 70;

    protected static function legacyComponent(): string
    {
        return 'admin.quality-manager';
    }
}
