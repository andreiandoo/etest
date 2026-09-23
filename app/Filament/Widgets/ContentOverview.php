<?php

namespace App\Filament\Widgets;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\TestAttempt;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Starea conținutului, dintr-o privire.
 *
 * Cifrele alese sunt cele care spun ce e de făcut, nu cele care arată bine:
 * ce așteaptă revizuire și ce a fost raportat sunt cozi de lucru, deci apar
 * colorate atunci când nu sunt goale.
 */
class ContentOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $publishedTests = TestDefinition::query()->published()->count();
        $publishedQuestions = Question::query()
            ->where('status', PublicationStatus::Published->value)
            ->count();
        $awaitingReview = Question::query()
            ->where('status', PublicationStatus::Review->value)
            ->count();
        $drafts = Question::query()
            ->where('status', PublicationStatus::Draft->value)
            ->count();
        $openReports = QuestionReport::query()->where('status', 'open')->count();
        $attemptsThisWeek = TestAttempt::query()
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        return [
            Stat::make('Domenii active', (string) Vertical::query()->active()->count())
                ->description('verticale publicate')
                ->icon(Heroicon::OutlinedSquares2x2),

            Stat::make('Teste publicate', number_format($publishedTests, 0, ',', ' '))
                ->description('vizibile pe site')
                ->icon(Heroicon::OutlinedClipboardDocumentList),

            Stat::make('Întrebări publicate', number_format($publishedQuestions, 0, ',', ' '))
                ->description($drafts.' în lucru')
                ->icon(Heroicon::OutlinedQuestionMarkCircle),

            Stat::make('Așteaptă revizuire', (string) $awaitingReview)
                ->description($awaitingReview === 0 ? 'nimic în coadă' : 'de verificat înainte de publicare')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color($awaitingReview > 0 ? 'warning' : 'gray'),

            Stat::make('Raportări deschise', (string) $openReports)
                ->description($openReports === 0 ? 'nicio sesizare' : 'semnalate de utilizatori')
                ->icon(Heroicon::OutlinedFlag)
                ->color($openReports > 0 ? 'danger' : 'gray'),

            Stat::make('Teste începute', number_format($attemptsThisWeek, 0, ',', ' '))
                ->description('în ultimele 7 zile')
                ->icon(Heroicon::OutlinedChartBarSquare),
        ];
    }
}
