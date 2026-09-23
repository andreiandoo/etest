<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Leads extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'lead-uri';

    protected static ?string $navigationLabel = 'Lead-uri';

    protected static ?string $title = 'Lead-uri';

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 20;

    protected static function legacyComponent(): string
    {
        return 'admin.lead-manager';
    }
}
