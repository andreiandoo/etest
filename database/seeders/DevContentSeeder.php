<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Enums\QuestionType;
use App\Enums\TaxonomyNodeType;
use App\Enums\TestMode;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Conținut demonstrativ pentru dezvoltare.
 *
 * Există ca să putem lucra la interfață pe date care arată a realitate:
 * verticale cu accente proprii, taxonomie pe două niveluri, teste publicate
 * și întrebări cu explicație și sursă.
 *
 * NU este conținut editorial. Fiecare întrebare poartă `metadata.demo = true`
 * și o etichetă de sursă care spune limpede că trebuie înlocuită. Handoff-ul
 * de producție interzice umplerea aplicației cu întrebări inventate, așa că
 * seeder-ul refuză să ruleze în producție fără o cerere explicită.
 *
 * Rulare:    php artisan db:seed --class=DevContentSeeder
 * Pe producție (set temporar de QA, permis de handoff la §28):
 *            ETEST_ALLOW_DEMO_CONTENT=true php artisan db:seed --class=DevContentSeeder --force
 * Curățare:  php artisan content:purge-demo
 */
class DevContentSeeder extends Seeder
{
    private const SOURCE_LABEL = 'Conținut demonstrativ — a se înlocui înainte de publicare';

    public function run(): void
    {
        // Handoff-ul permite un set temporar de date de QA în producție, marcat
        // clar și șters înainte de lansare. Permitem asta, dar numai la cerere
        // explicită: altfel un `db:seed --force` dintr-un script ar umple
        // producția cu conținut inventat fără ca nimeni să fi decis asta.
        if (app()->isProduction() && ! env('ETEST_ALLOW_DEMO_CONTENT', false)) {
            $this->command?->error('Refuz să rulez în producție.');
            $this->command?->line('Dacă vrei conținut de QA pe producție, pornește o singură dată cu ETEST_ALLOW_DEMO_CONTENT=true,');
            $this->command?->line('apoi șterge-l cu `php artisan content:purge-demo` înainte de publicare.');

            return;
        }

        foreach ($this->blueprint() as $index => $verticalData) {
            $vertical = Vertical::query()->updateOrCreate(
                ['slug' => $verticalData['slug']],
                [
                    'name' => $verticalData['name'],
                    'description' => $verticalData['description'],
                    'is_active' => true,
                    'sort_order' => $index,
                    'seo_title' => $verticalData['name'].' — teste grilă gratuite | e-test.ro',
                    'seo_description' => $verticalData['description'],
                    'metadata' => [
                        'demo' => true,
                        'icon' => $verticalData['icon'],
                        'accent' => ['palette' => $verticalData['palette']],
                    ],
                ],
            );

            foreach ($verticalData['nodes'] as $nodeIndex => $nodeData) {
                $node = TaxonomyNode::query()->updateOrCreate(
                    ['vertical_id' => $vertical->id, 'slug' => $nodeData['slug']],
                    [
                        'parent_id' => null,
                        'type' => $nodeData['type'],
                        'name' => $nodeData['name'],
                        'description' => $nodeData['description'],
                        'seo_title' => $nodeData['name'].' — '.$vertical->name.' | e-test.ro',
                        'seo_description' => $nodeData['description'],
                        'sort_order' => $nodeIndex,
                        'is_active' => true,
                        'metadata' => ['demo' => true],
                    ],
                );

                $questions = $this->seedQuestions($vertical, $node, $nodeData['questions']);

                foreach ($nodeData['tests'] as $testIndex => $testData) {
                    $this->seedTest($vertical, $node, $testData, $testIndex, $questions);
                }
            }
        }

        $this->command?->info('Conținut demonstrativ creat. Toate înregistrările poartă metadata.demo = true.');
    }

    /**
     * @param  array<int, array{prompt: string, explanation: string, source: string, options: array<int, array{0: string, 1: bool}>}>  $definitions
     * @return array<int, Question>
     */
    private function seedQuestions(Vertical $vertical, TaxonomyNode $node, array $definitions): array
    {
        $questions = [];

        foreach ($definitions as $position => $definition) {
            $question = Question::query()->updateOrCreate(
                [
                    'vertical_id' => $vertical->id,
                    'source_key' => $node->slug.'-'.($position + 1),
                ],
                [
                    'taxonomy_node_id' => $node->id,
                    'type' => QuestionType::SingleChoice->value,
                    'status' => PublicationStatus::Published->value,
                    'prompt' => $definition['prompt'],
                    'explanation' => $definition['explanation'],
                    'difficulty' => 3,
                    'source_label' => $definition['source'].' · '.self::SOURCE_LABEL,
                    'source_checked_at' => Carbon::now()->subDays(random_int(3, 40))->toDateString(),
                    'answer_config' => [],
                    'metadata' => ['demo' => true],
                ],
            );

            // Opțiunile se rescriu de la zero: altfel o rulare repetată le-ar dubla.
            AnswerOption::query()->where('question_id', $question->id)->delete();

            foreach ($definition['options'] as $optionPosition => [$content, $isCorrect]) {
                AnswerOption::query()->create([
                    'question_id' => $question->id,
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'position' => $optionPosition,
                ]);
            }

            $questions[] = $question;
        }

        return $questions;
    }

    /**
     * @param  array{title: string, slug: string, description: string, mode: TestMode, minutes: int|null}  $testData
     * @param  array<int, Question>  $questions
     */
    private function seedTest(
        Vertical $vertical,
        TaxonomyNode $node,
        array $testData,
        int $sortIndex,
        array $questions,
    ): void {
        $test = TestDefinition::query()->updateOrCreate(
            ['vertical_id' => $vertical->id, 'slug' => $testData['slug']],
            [
                'taxonomy_node_id' => $node->id,
                'title' => $testData['title'],
                'description' => $testData['description'],
                'seo_title' => $testData['title'].' — '.$vertical->name.' | e-test.ro',
                'seo_description' => $testData['description'],
                'mode' => $testData['mode']->value,
                'status' => PublicationStatus::Published->value,
                'question_limit' => count($questions),
                'duration_seconds' => $testData['minutes'] !== null ? $testData['minutes'] * 60 : null,
                'passing_percentage' => 80,
                'randomize_questions' => $testData['mode'] === TestMode::Exam,
                'randomize_options' => $testData['mode'] === TestMode::Exam,
                'allow_review' => true,
                'show_explanations' => $testData['mode'] !== TestMode::Exam,
                'metadata' => ['demo' => true],
                'published_at' => Carbon::now()->subDays(($sortIndex + 1) * 3),
            ],
        );

        $test->questions()->sync(
            collect($questions)
                ->values()
                ->mapWithKeys(fn (Question $question, int $index): array => [
                    $question->id => ['position' => $index + 1, 'points' => 1, 'required' => true],
                ])
                ->all(),
        );
    }

    /**
     * Structura de conținut. Un singur loc de modificat când vrem alte domenii.
     *
     * @return array<int, array<string, mixed>>
     */
    private function blueprint(): array
    {
        return [
            [
                'slug' => 'auto',
                'name' => 'Auto & DRPCIV',
                'palette' => 'auto',
                'icon' => 'wheel',
                'description' => 'Chestionare pentru permisul auto, pe structura probei teoretice: legislație rutieră, indicatoare, marcaje și conduită preventivă.',
                'nodes' => [
                    [
                        'slug' => 'categoria-b',
                        'type' => TaxonomyNodeType::Exam->value,
                        'name' => 'Categoria B',
                        'description' => 'Proba teoretică pentru categoria B: autoturisme și autovehicule până la 3.500 kg.',
                        'questions' => [
                            [
                                'prompt' => 'Ce formă și ce culoare de fond au indicatoarele de avertizare a pericolului?',
                                'explanation' => 'Indicatoarele de avertizare sunt triunghiulare, cu fond alb și bordură roșie. Forma le distinge la distanță, înainte ca simbolul să devină lizibil.',
                                'source' => 'Regulamentul de aplicare a OUG 195/2002',
                                'options' => [
                                    ['Triunghi cu fond alb și bordură roșie', true],
                                    ['Cerc cu fond albastru', false],
                                    ['Pătrat cu fond galben', false],
                                ],
                            ],
                            [
                                'prompt' => 'Ce obligație impune marcajul longitudinal continuu?',
                                'explanation' => 'Marcajul longitudinal continuu nu poate fi încălcat. El separă sensurile sau benzile acolo unde depășirea ori schimbarea benzii ar fi periculoasă.',
                                'source' => 'Regulamentul de aplicare a OUG 195/2002',
                                'options' => [
                                    ['Nu poate fi încălcat', true],
                                    ['Poate fi încălcat doar noaptea', false],
                                    ['Poate fi încălcat pentru depășire', false],
                                ],
                            ],
                            [
                                'prompt' => 'Într-o intersecție nedirijată prin indicatoare sau semafoare, cui se acordă prioritate?',
                                'explanation' => 'Se aplică regula priorității de dreapta: conducătorul acordă prioritate vehiculelor care vin din partea dreaptă.',
                                'source' => 'OUG 195/2002',
                                'options' => [
                                    ['Vehiculelor care vin din dreapta', true],
                                    ['Vehiculelor care vin din stânga', false],
                                    ['Vehiculului mai mare', false],
                                ],
                            ],
                            [
                                'prompt' => 'Ce semnificație are semaforul cu lumină galbenă intermitentă?',
                                'explanation' => 'Galbenul intermitent nu oprește circulația, dar obligă la deplasare cu atenție sporită, respectând regulile de prioritate din intersecție.',
                                'source' => 'OUG 195/2002',
                                'options' => [
                                    ['Trecere permisă, cu atenție sporită', true],
                                    ['Oprire obligatorie', false],
                                    ['Interzice complet accesul', false],
                                ],
                            ],
                        ],
                        'tests' => [
                            [
                                'title' => 'Categoria B — chestionar complet',
                                'slug' => 'categoria-b-chestionar-complet',
                                'description' => 'Simulare cronometrată pe structura probei teoretice, cu rezultatul afișat abia la final, ca la examen.',
                                'mode' => TestMode::Exam,
                                'minutes' => 30,
                            ],
                            [
                                'title' => 'Indicatoare și marcaje — exersare',
                                'slug' => 'indicatoare-si-marcaje',
                                'description' => 'Exersare fără cronometru, cu explicația și sursa afișate imediat după fiecare răspuns.',
                                'mode' => TestMode::Practice,
                                'minutes' => null,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'drept',
                'name' => 'Drept & Barou',
                'palette' => 'legal',
                'icon' => 'scales',
                'description' => 'Grile pentru admiterea în profesiile juridice și pentru concursurile din administrația publică, organizate pe materiile din tematica oficială.',
                'nodes' => [
                    [
                        'slug' => 'admitere-barou',
                        'type' => TaxonomyNodeType::Exam->value,
                        'name' => 'Admitere în Barou',
                        'description' => 'Grile din drept civil, procedură civilă, drept penal, procedură penală și organizarea profesiei de avocat.',
                        'questions' => [
                            [
                                'prompt' => 'Care este termenul general de prescripție extinctivă în materia drepturilor patrimoniale?',
                                'explanation' => 'Termenul general de prescripție este de 3 ani, dacă legea nu prevede altfel. Termenele speciale sunt sursa clasică de capcane în grile.',
                                'source' => 'Codul civil',
                                'options' => [
                                    ['3 ani', true],
                                    ['1 an', false],
                                    ['2 ani', false],
                                    ['10 ani', false],
                                ],
                            ],
                            [
                                'prompt' => 'Ce act normativ reglementează organizarea și exercitarea profesiei de avocat în România?',
                                'explanation' => 'Profesia este reglementată de Legea nr. 51/1995, completată de Statutul profesiei de avocat.',
                                'source' => 'Legea 51/1995',
                                'options' => [
                                    ['Legea nr. 51/1995', true],
                                    ['Legea nr. 303/2004', false],
                                    ['Legea nr. 31/1990', false],
                                ],
                            ],
                            [
                                'prompt' => 'Care este organismul profesional care organizează examenul de admitere în profesia de avocat?',
                                'explanation' => 'Examenul este organizat de Uniunea Națională a Barourilor din România, care publică anual tematica și bibliografia.',
                                'source' => 'Legea 51/1995',
                                'options' => [
                                    ['Uniunea Națională a Barourilor din România', true],
                                    ['Ministerul Justiției', false],
                                    ['Consiliul Superior al Magistraturii', false],
                                ],
                            ],
                        ],
                        'tests' => [
                            [
                                'title' => 'Barou — simulare completă',
                                'slug' => 'barou-simulare-completa',
                                'description' => 'Simulare cronometrată din toate materiile de examen, în proporția din tematica oficială.',
                                'mode' => TestMode::Exam,
                                'minutes' => 240,
                            ],
                            [
                                'title' => 'Drept civil — prescripția extinctivă',
                                'slug' => 'drept-civil-prescriptia-extinctiva',
                                'description' => 'Exersare pe capitol, cu articolul citat la fiecare răspuns.',
                                'mode' => TestMode::Practice,
                                'minutes' => null,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'medicina',
                'name' => 'Medicină & Sănătate',
                'palette' => 'medical',
                'icon' => 'pulse',
                'description' => 'Grile pentru rezidențiat, asistenți medicali și farmacie, organizate pe materii și capitole, cu bibliografia citată.',
                'nodes' => [
                    [
                        'slug' => 'rezidentiat',
                        'type' => TaxonomyNodeType::Exam->value,
                        'name' => 'Rezidențiat',
                        'description' => 'Grile pe materiile din tematica de rezidențiat, cu explicație și referință bibliografică.',
                        'questions' => [
                            [
                                'prompt' => 'Care este unitatea de măsură pentru tensiunea arterială folosită curent în practica clinică?',
                                'explanation' => 'Tensiunea arterială se exprimă în milimetri coloană de mercur (mmHg), prin convenție internațională.',
                                'source' => 'Bibliografie de specialitate',
                                'options' => [
                                    ['Milimetri coloană de mercur (mmHg)', true],
                                    ['Pascali (Pa)', false],
                                    ['Bari (bar)', false],
                                ],
                            ],
                            [
                                'prompt' => 'Ce investigație înregistrează activitatea electrică a inimii?',
                                'explanation' => 'Electrocardiograma înregistrează activitatea electrică a cordului, spre deosebire de ecocardiografie, care este o investigație imagistică.',
                                'source' => 'Bibliografie de specialitate',
                                'options' => [
                                    ['Electrocardiograma', true],
                                    ['Ecocardiografia', false],
                                    ['Radiografia toracică', false],
                                ],
                            ],
                            [
                                'prompt' => 'Care este valoarea normală a frecvenței cardiace de repaus la adultul sănătos?',
                                'explanation' => 'Intervalul de referință acceptat pentru adultul sănătos în repaus este 60–100 bătăi pe minut.',
                                'source' => 'Bibliografie de specialitate',
                                'options' => [
                                    ['60–100 bătăi pe minut', true],
                                    ['30–50 bătăi pe minut', false],
                                    ['120–160 bătăi pe minut', false],
                                ],
                            ],
                        ],
                        'tests' => [
                            [
                                'title' => 'Rezidențiat — test de diagnostic',
                                'slug' => 'rezidentiat-test-diagnostic',
                                'description' => 'Un test scurt care îți arată de la ce nivel pornești și ce capitole trebuie reluate.',
                                'mode' => TestMode::Practice,
                                'minutes' => null,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'certificari-it',
                'name' => 'IT & Certificări',
                'palette' => 'it',
                'icon' => 'terminal',
                'description' => 'Teste pentru certificări IT: competențe digitale, fundamente cloud și securitate, în formatul examenelor reale.',
                'nodes' => [
                    [
                        'slug' => 'competente-digitale',
                        'type' => TaxonomyNodeType::Certification->value,
                        'name' => 'Competențe digitale',
                        'description' => 'Module de bază: sisteme de operare, procesare de text, calcul tabelar și securitate elementară.',
                        'questions' => [
                            [
                                'prompt' => 'Ce reprezintă extensia unui fișier?',
                                'explanation' => 'Extensia indică formatul fișierului și ajută sistemul de operare să aleagă aplicația potrivită pentru deschiderea lui.',
                                'source' => 'Programa de competențe digitale',
                                'options' => [
                                    ['Formatul fișierului', true],
                                    ['Dimensiunea fișierului', false],
                                    ['Data creării fișierului', false],
                                ],
                            ],
                            [
                                'prompt' => 'În calculul tabelar, ce desemnează o referință absolută de tipul $A$1?',
                                'explanation' => 'Simbolul dolar fixează coloana și linia, astfel încât referința nu se modifică la copierea formulei în alte celule.',
                                'source' => 'Programa de competențe digitale',
                                'options' => [
                                    ['O referință care nu se modifică la copiere', true],
                                    ['O celulă care conține o sumă în valută', false],
                                    ['O celulă protejată prin parolă', false],
                                ],
                            ],
                            [
                                'prompt' => 'Ce indică prefixul „https” din adresa unui site?',
                                'explanation' => 'HTTPS înseamnă că traficul dintre browser și server este criptat. Nu garantează însă că site-ul este legitim sau de încredere.',
                                'source' => 'Programa de competențe digitale',
                                'options' => [
                                    ['Că traficul este criptat', true],
                                    ['Că site-ul este verificat de stat', false],
                                    ['Că site-ul nu folosește cookie-uri', false],
                                ],
                            ],
                        ],
                        'tests' => [
                            [
                                'title' => 'Competențe digitale — modulul de bază',
                                'slug' => 'competente-digitale-modul-baza',
                                'description' => 'Test în formatul examenului real, cu rezultat la final.',
                                'mode' => TestMode::Exam,
                                'minutes' => 45,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'limbi-straine',
                'name' => 'Limbi străine',
                'palette' => 'lang',
                'icon' => 'globe',
                'description' => 'Teste de nivel pentru engleză, germană și franceză, structurate pe nivelurile Cadrului European Comun de Referință.',
                'nodes' => [
                    [
                        'slug' => 'engleza-b2',
                        'type' => TaxonomyNodeType::Subject->value,
                        'name' => 'Engleză nivel B2',
                        'description' => 'Gramatică, vocabular și înțelegerea textului la nivelul B2 din cadrul european.',
                        'questions' => [
                            [
                                'prompt' => 'Alege forma corectă: „If I ___ more time, I would travel more.”',
                                'explanation' => 'Condiționalul de tipul al doilea cere forma „had” la trecut simplu în propoziția subordonată, pentru o situație ipotetică din prezent.',
                                'source' => 'Cadrul European Comun de Referință',
                                'options' => [
                                    ['had', true],
                                    ['have', false],
                                    ['will have', false],
                                ],
                            ],
                            [
                                'prompt' => 'Care este sensul expresiei „to look forward to something”?',
                                'explanation' => 'Expresia înseamnă a aștepta ceva cu nerăbdare. Este urmată de substantiv sau de verb în forma cu -ing.',
                                'source' => 'Cadrul European Comun de Referință',
                                'options' => [
                                    ['A aștepta ceva cu nerăbdare', true],
                                    ['A privi înainte', false],
                                    ['A uita ceva', false],
                                ],
                            ],
                            [
                                'prompt' => 'Alege varianta corectă: „She has been working here ___ 2019.”',
                                'explanation' => 'Pentru un moment de început precis se folosește „since”. „For” se folosește pentru durate.',
                                'source' => 'Cadrul European Comun de Referință',
                                'options' => [
                                    ['since', true],
                                    ['for', false],
                                    ['from', false],
                                ],
                            ],
                        ],
                        'tests' => [
                            [
                                'title' => 'Engleză B2 — test de nivel',
                                'slug' => 'engleza-b2-test-de-nivel',
                                'description' => 'Test de plasare pe nivel, cu explicație după fiecare răspuns.',
                                'mode' => TestMode::Practice,
                                'minutes' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
