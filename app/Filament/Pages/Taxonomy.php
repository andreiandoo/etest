<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Taxonomy extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'taxonomie';

    protected static ?string $navigationLabel = 'Taxonomie';

    protected static ?string $title = 'Taxonomie';

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 20;

    protected static function legacyComponent(): string
    {
        return 'admin.taxonomy-manager';
    }
}
