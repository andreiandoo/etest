<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Questions extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'intrebari';

    protected static ?string $navigationLabel = 'Întrebări';

    protected static ?string $title = 'Întrebări';

    protected static string | UnitEnum | null $navigationGroup = 'Conținut';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 40;

    protected static function legacyComponent(): string
    {
        return 'admin.question-manager';
    }
}
