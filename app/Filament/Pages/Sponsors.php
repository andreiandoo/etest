<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Sponsors extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'sponsori';

    protected static ?string $navigationLabel = 'Sponsori';

    protected static ?string $title = 'Sponsori';

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 10;

    protected static function legacyComponent(): string
    {
        return 'admin.sponsorship-manager';
    }
}
