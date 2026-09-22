<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Seo\StructuredData;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(
        StructuredData $structuredData,
        TenantContext $tenantContext,
        PublicUrlGenerator $urls,
    ): View {
        $allowedVerticalIds = $tenantContext->allowedVerticalIds();

        $verticals = Vertical::query()
            ->active()
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('id', $allowedVerticalIds))
            ->withCount([
                'tests as published_tests_count' => fn ($query) => $query
                    ->where('status', PublicationStatus::Published->value)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
                'questions as published_questions_count' => fn ($query) => $query
                    ->where('status', PublicationStatus::Published->value),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Popularitatea se măsoară în încercări, nu în vizualizări: un test pe
        // care oamenii chiar îl dau spune mai mult decât unul pe care îl deschid.
        $popularTests = TestDefinition::query()
            ->published()
            ->with(['vertical', 'taxonomyNode'])
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->withCount('attempts')
            ->orderByDesc('attempts_count')
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        $recentTests = TestDefinition::query()
            ->published()
            ->with(['vertical', 'taxonomyNode'])
            ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $brandName = (string) $tenantContext->brand('site_name', 'e-test.ro');
        $brandDescription = (string) $tenantContext->brand(
            'seo_description',
            'Teste online gratuite pentru examene, certificări și evaluări. Exersează pe domenii, urmărește progresul și revino la punctele slabe.',
        );

        return view('home', [
            'verticals' => $verticals,
            'popularTests' => $popularTests,
            'recentTests' => $recentTests,
            'publishedTestsCount' => TestDefinition::query()
                ->published()
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
            'publishedQuestionsCount' => Question::query()
                ->where('status', PublicationStatus::Published->value)
                ->when($allowedVerticalIds !== null, fn ($query) => $query->whereIn('vertical_id', $allowedVerticalIds))
                ->count(),
            'urlGenerator' => $urls,
            'faq' => self::faq(),
            'canonical' => route('home'),
            'seoTitle' => (string) $tenantContext->brand(
                'seo_title',
                $brandName.' – teste online gratuite pentru examene și certificări',
            ),
            'seoDescription' => $brandDescription,
            'structuredData' => [$structuredData->website()],
        ]);
    }

    /**
     * Întrebările frecvente de pe homepage.
     *
     * Stau aici, nu în view, ca să poată fi refolosite la marcajul FAQPage
     * fără să duplicăm textul.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private static function faq(): array
    {
        return [
            [
                'question' => 'Chiar sunt gratuite toate testele?',
                'answer' => 'Da. Nu există versiune plătită, pachet premium sau limită de teste pe zi. Platforma se susține din parteneriate afișate separat de conținut, niciodată în timpul testului.',
            ],
            [
                'question' => 'De ce am nevoie de cont ca să dau un test?',
                'answer' => 'Ca să-ți putem salva răspunsurile, progresul și capitolele slabe. Fără cont am putea afișa întrebări, dar n-am avea unde reține nimic — iar atunci platforma ar fi doar o listă de întrebări.',
            ],
            [
                'question' => 'De unde vin întrebările și cât de actuale sunt?',
                'answer' => 'Fiecare întrebare are referința citată și data ultimei verificări, afișate lângă răspuns. Dacă găsești ceva depășit, raportezi dintr-un clic și intră la revizuire.',
            ],
            [
                'question' => 'Pot exersa de pe telefon?',
                'answer' => 'Da. Interfața e construită întâi pentru telefon, fiindcă acolo se exersează cel mai des — în pauze, pe drum, seara.',
            ],
            [
                'question' => 'Testele înlocuiesc școala de șoferi sau cursul de admitere?',
                'answer' => 'Nu. Sunt antrenamentul pe grile care însoțește cursul sau studiul individual — partea de repetiție și de diagnostic, nu predarea materiei.',
            ],
        ];
    }
}
