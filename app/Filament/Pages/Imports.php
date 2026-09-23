<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Imports extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'importuri';

    protected static ?string $navigationLabel = 'Importuri';

    protected static ?string $title = 'Importuri';

    protected static string | UnitEnum | null $navigationGroup = 'Conținut';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 50;

    protected static function legacyComponent(): string
    {
        return 'admin.import-manager';
    }
}
