<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Affiliate extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'afiliere';

    protected static ?string $navigationLabel = 'Afiliere';

    protected static ?string $title = 'Afiliere';

    protected static string | UnitEnum | null $navigationGroup = 'Monetizare';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?int $navigationSort = 30;

    protected static function legacyComponent(): string
    {
        return 'admin.affiliate-manager';
    }
}
