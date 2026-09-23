<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\WrapsLegacyManager;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Tests extends Page
{
    use WrapsLegacyManager;

    protected static ?string $slug = 'teste';

    protected static ?string $navigationLabel = 'Teste';

    protected static ?string $title = 'Teste';

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    protected static function legacyComponent(): string
    {
        return 'admin.test-manager';
    }
}
