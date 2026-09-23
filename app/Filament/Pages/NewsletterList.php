<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class NewsletterList extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'newsletter';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?string $title = 'Newsletter';

    protected static string|UnitEnum|null $navigationGroup = 'Monetizare';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 40;

    protected static function legacyComponent(): string
    {
        return 'admin.newsletter-manager';
    }
}
