<?php

namespace App\Services\Sources\Connectors;

use App\Enums\QuestionType;
use App\Enums\TestMode;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Services\Content\PracticeTestBuilder;
use App\Services\Sources\Contracts\LocalDocumentSource;
use App\Services\Sources\Parsers\IsfCsvParser;
use RuntimeException;

/**
 * Institutul de Studii Financiare — băncile de întrebări pentru certificarea în
 * asigurări.
 *
 * Institutul publică întrebările cu răspunsul corect marcat, ceea ce e rar și
 * bun. Le publică însă în PDF, ca tabel pe trei coloane, iar tabelul acela nu se
 * lasă citit curat: coloanele ies decalate, iar fișierul pentru conducători e
 * scanat integral. Așa că trec printr-o transcriere în CSV, iar de aici încolo
 * conducta e simplă.
 *
 * Sunt două examene diferite, cu două secțiuni surori în catalog, deci doi
 * conectori pe același cod. Împart un fond comun — patru sute de întrebări apar
 * în amândouă — și e firesc: un conducător de societate dă și el examenul de
 * bază, plus partea lui. Fiecare secțiune își ține copia ei, fiindcă un candidat
 * la un examen trebuie să aibă fondul întreg, nu ciuruit de trimiteri.
 */
abstract class IsfConnector implements LocalDocumentSource
{
    public function __construct(
        private readonly IsfCsvParser $parser,
        private readonly PracticeTestBuilder $tests,
    ) {}

    /**
     * Bucata din numele fișierului care deosebește examenul acesta de celălalt.
     */
    abstract protected function fileMarker(): string;

    /**
     * Secțiunea din catalog în care intră întrebările.
     */
    abstract protected function examSlug(): string;

    /**
     * Numele examenului, așa cum intră în titluri.
     */
    abstract protected function examName(): string;

    public function directory(): string
    {
        return 'docs/ISF';
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->key(),
            'authority' => 'ISF — Institutul de Studii Financiare',
            'title' => 'Banca de întrebări pentru examenul de '.$this->examName(),
            'exam' => 'Examen de certificare, '.$this->examName(),
            'specialty' => 'Distribuție de asigurări, legislație și conduită profesională',
            'vertical_slug' => 'asigurari',
            'taxonomy_slug' => $this->examSlug(),
            'source_page_url' => 'https://platforma.isfin.ro/ro/examinari-online',
            'document_url' => 'https://platforma.isfin.ro/ro/examinari-online',
            'license_url' => null,
            'version_label' => 'actualizare 16.01.2026',
            'file_format' => 'csv',
            'rights_status' => 'official_public_unclear',
            'answer_key' => 'embedded',
            'explanations' => 'none',
            'import_difficulty' => 'mixed',
            'count_status' => 'counted_exact',
            'notes' => 'Institutul publică întrebările cu răspunsul corect marcat printr-un asterisc, dar în '
                .'PDF, ca tabel pe trei coloane care nu se lasă citit curat; fișierul pentru conducători e '
                .'chiar scanat. Fișierele din depozit sunt transcrierea lor în CSV. Rândurile cărora le '
                .'lipsește litera — în PDF aveau asterisc două variante sau niciuna — se raportează, nu se '
                .'ghicesc.',
        ];
    }

    public function prepare(Source $source): void
    {
        if ($source->taxonomyNode === null) {
            throw new RuntimeException('Sursa nu are o secțiune de taxonomie în care să pună întrebările.');
        }
    }

    /**
     * @param  array<string, string>  $paths
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parseDocuments(array $paths): array
    {
        $path = $this->fileFor($paths);

        if ($path === null) {
            return ['total' => 0, 'questions' => [], 'rejected' => [[
                'code' => $this->examName(),
                'reason' => 'Nu am găsit în '.$this->directory().' niciun fișier CSV cu „'
                    .$this->fileMarker().'” în nume.',
                'text' => '',
            ]]];
        }

        return $this->parser->parse($path);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array
    {
        $rows = [];

        foreach ($questions as $question) {
            $correct = (int) $question['correct'];
            $options = [];

            foreach ((array) $question['options'] as $index => $content) {
                $options[] = [
                    'content' => (string) $content,
                    'is_correct' => $correct === $index + 1,
                ];
            }

            $prompt = (string) $question['prompt'];

            $rows[] = [
                // Cele două examene împart un fond comun, dar fiecare își ține
                // copia lui: cheia poartă examenul, ca o întrebare comună să
                // existe în ambele secțiuni, nu doar în cea sincronizată ultima.
                'source_key' => 'isf:'.$this->examSlug().':'
                    .substr(sha1($prompt."\n".implode("\n", (array) $question['options'])), 0, 12),
                'type' => QuestionType::SingleChoice->value,
                'prompt' => $prompt,
                'explanation' => '',
                'taxonomy_slug' => $this->examSlug(),
                'source_label' => 'ISF — bancă de întrebări, examenul de '.$this->examName(),
                'source_url' => $source->source_page_url,
                'source_checked_at' => today()->toDateString(),
                'answer_config' => [],
                'options' => $options,
                'metadata' => [
                    'exam' => $this->examSlug(),
                    'exam_number' => $question['number'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Simularea probei, după regulile scrise în catalog.
     *
     * Unde regulile lipsesc — nu le știm pentru toate examenele Institutului —
     * iese un test de exercițiu fără ceas, nu o simulare care pretinde reguli
     * inventate.
     */
    public function buildTests(Source $source): int
    {
        $node = $source->taxonomyNode;

        if ($node === null) {
            return 0;
        }

        $exam = (array) data_get($node->metadata, 'exam');
        $questions = (int) ($exam['questions'] ?? 0);
        $duration = (int) ($exam['duration_seconds'] ?? 0);
        $passing = (int) ($exam['passing_questions'] ?? 0);
        $wrong = (int) ($exam['max_wrong'] ?? 0);

        if ($questions < 1) {
            $practice = $this->tests->build(
                $node,
                $this->examSlug().'-exercitii',
                $this->examName().' — test de exercițiu',
                'Întrebări din banca publicată de Institutul de Studii Financiare pentru examenul de '
                    .$this->examName().'.',
                40,
                dynamic: true,
            );

            return $practice === null ? 0 : 1;
        }

        $simulation = $this->tests->build(
            $node,
            $this->examSlug().'-examen',
            $node->name.' — simulare examen',
            'Proba de certificare pentru '.mb_strtolower($this->examName()).', în formatul ei: '.$questions
                .' întrebări în '.intdiv($duration, 60).' de minute, cu '.$passing
                .' răspunsuri corecte pentru promovare.',
            $questions,
            mode: TestMode::Exam,
            durationSeconds: $duration > 0 ? $duration : null,
            passingQuestions: $passing > 0 ? $passing : null,
            maxWrongAnswers: $wrong > 0 ? $wrong : null,
            dynamic: true,
        );

        return $simulation === null ? 0 : 1;
    }

    /**
     * Fișierul acestui examen, recunoscut după o bucată din nume.
     *
     * Numele vin de la Institut, cu spații și diacritice, și se schimbă la
     * fiecare actualizare — „16.01.2026” e chiar în ele. De aceea se caută o
     * bucată stabilă, nu numele întreg.
     *
     * @param  array<string, string>  $paths
     */
    private function fileFor(array $paths): ?string
    {
        foreach ($paths as $name => $path) {
            if (! str_ends_with(mb_strtolower($name), '.csv')) {
                continue;
            }

            if (mb_stripos($name, $this->fileMarker()) !== false) {
                return $path;
            }
        }

        return null;
    }
}
