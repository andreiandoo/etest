<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Review extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'revizuire';

    protected static ?string $navigationLabel = 'Revizuire';

    protected static ?string $title = 'Revizuire';

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?int $navigationSort = 60;

    protected static function legacyComponent(): string
    {
        return 'admin.review-queue';
    }
}
