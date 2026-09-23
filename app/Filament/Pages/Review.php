<?php

namespace App\Filament\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Widgets\QuestionsAwaitingReview;
use App\Filament\Widgets\TestsAwaitingReview;
use App\Models\Question;
use App\Models\TestDefinition;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Coada de revizuire.
 *
 * Întrebările și testele în revizuire se pot vedea și din filele resurselor
 * lor, dar decizia de publicare e o treabă de sine stătătoare, nu o rătăcire
 * prin catalog: aici sunt amândouă cozile într-un singur ecran, cu aceleași
 * acțiuni, ca revizorul să nu sară între două liste.
 */
class Review extends Page
{
    protected static ?string $slug = 'revizuire';

    protected static ?string $navigationLabel = 'Revizuire';

    protected static ?string $title = 'Revizuire';

    protected ?string $subheading = 'Ce așteaptă decizia de publicare.';

    protected static string|UnitEnum|null $navigationGroup = 'Conținut';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?int $navigationSort = 60;

    public static function getNavigationBadge(): ?string
    {
        $waiting = Question::query()->where('status', PublicationStatus::Review->value)->count()
            + TestDefinition::query()->where('status', PublicationStatus::Review->value)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            QuestionsAwaitingReview::class,
            TestsAwaitingReview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
