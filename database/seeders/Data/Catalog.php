<?php

namespace Database\Seeders\Data;

use App\Enums\TaxonomyNodeType;

/**
 * Catalogul de examene al platformei.
 *
 * Datele vin din cele două documente de cercetare: `e-test.ro_research_dossier`
 * pentru verticale, examene și surse oficiale, `e-test.ro_content_inventory`
 * pentru volumele măsurate și starea numărătorii.
 *
 * Fișierul e doar date. Regulile de scriere în bază stau în `CatalogSeeder`,
 * ca structura să poată fi citită și corectată de cineva care nu vrea să
 * citească și logica de import.
 *
 * `rights` folosește vocabularul din §31 al dosarului. Nu blochează nimic aici
 * — catalogul descrie examene, nu republică întrebări — dar rămâne lipit de
 * fiecare secțiune, ca decizia de import să nu se ia mai târziu din memorie.
 */
final class Catalog
{
    public const RIGHTS_EXPLICIT = 'explicit_allowed';

    public const RIGHTS_UNCLEAR = 'official_public_unclear';

    public const RIGHTS_RESTRICTED = 'restricted';

    public const RIGHTS_ORIGINAL = 'original_content';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function verticals(): array
    {
        return [
            self::auto(),
            self::transport(),
            self::medicina(),
            self::drept(),
            self::administratie(),
            self::radio(),
            self::vanatoare(),
            self::radioprotectie(),
            self::finante(),
            self::fiscal(),
            self::insolventa(),
            self::energie(),
            self::ordinePublica(),
            self::drone(),
            self::tehnic(),
            self::constructii(),
            self::naval(),
            self::feroviar(),
            self::evaluare(),
            self::asigurari(),
            self::cadastru(),
            self::agricultura(),
            self::proprietateIntelectuala(),
            self::contabilitate(),
            self::admitere(),
            self::educatie(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function auto(): array
    {
        return [
            'slug' => 'auto',
            'name' => 'Auto',
            'palette' => 'auto',
            'icon' => 'wheel',
            'authority' => 'DGPCI — Ministerul Afacerilor Interne',
            'source_url' => 'https://dgpci.mai.gov.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Pregătire pentru proba teoretică a permisului de conducere, pe categorii. Întrebări de legislație rutieră, conduită preventivă și mecanică, în formatul examenului oficial.',
            'seo_title' => 'Chestionare auto gratuite 2026 — permis de conducere | e-test.ro',
            'seo_description' => 'Chestionare auto gratuite pentru categoriile A, B, C și D. Întrebări în formatul probei teoretice, cu explicații și trimitere la articolul de lege.',
            'nodes' => [
                [
                    'slug' => 'permis-categoria-b',
                    'name' => 'Permis categoria B',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Proba teoretică pentru autoturisme. Categoria cu cei mai mulți candidați și cea mai căutată pregătire online din România.',
                ],
                [
                    'slug' => 'permis-categoria-a',
                    'name' => 'Permis categoria A',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Proba teoretică pentru motociclete, cu accent pe conduită preventivă și particularitățile vehiculelor pe două roți.',
                ],
                [
                    'slug' => 'permis-categoria-c',
                    'name' => 'Permis categoria C',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Proba teoretică pentru autovehicule destinate transportului de mărfuri, inclusiv reguli de masă și gabarit.',
                ],
                [
                    'slug' => 'permis-categoria-d',
                    'name' => 'Permis categoria D',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Proba teoretică pentru transportul de persoane, cu reguli specifice de siguranță a călătorilor.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function transport(): array
    {
        return [
            'slug' => 'transport',
            'name' => 'Transport',
            'palette' => 'transport',
            'icon' => 'truck',
            'authority' => 'ARR — Autoritatea Rutieră Română',
            'source_url' => 'https://www.arr.ro/servicii_doc_164_eliberare-atestate_pg_0.htm',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Atestate profesionale pentru transportul rutier: marfă, persoane, ADR, taxi, manager de transport și consilier de siguranță. O singură instituție acoperă peste zece examene distincte.',
            'seo_title' => 'Atestate ARR — teste grilă gratuite pentru transport | e-test.ro',
            'seo_description' => 'Teste gratuite pentru atestatele ARR: CPI, CPC, ADR, taxi, manager de transport și consilier de siguranță, în formatul examenului oficial.',
            'nodes' => [
                [
                    'slug' => 'cpi-marfa',
                    'name' => 'CPI marfă',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de pregătire inițială pentru transportul rutier de mărfuri.',
                    'items' => 355,
                    'children' => [
                        [
                            'slug' => 'studii-de-caz',
                            'name' => 'Studii de caz CPI marfă',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Situații practice de transport marfă, evaluate pe caz, nu pe întrebare izolată.',
                            'items' => 122,
                        ],
                    ],
                ],
                [
                    'slug' => 'cpi-persoane',
                    'name' => 'CPI persoane',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de pregătire inițială pentru transportul rutier de persoane.',
                    'items' => 329,
                    'children' => [
                        [
                            'slug' => 'studii-de-caz',
                            'name' => 'Studii de caz CPI persoane',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Situații practice de transport persoane, evaluate pe caz.',
                            'items' => 137,
                        ],
                    ],
                ],
                [
                    'slug' => 'cpc-marfa',
                    'name' => 'CPC marfă',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de pregătire continuă pentru conducătorii auto de marfă, obligatoriu periodic.',
                    'items' => 243,
                ],
                [
                    'slug' => 'cpc-persoane',
                    'name' => 'CPC persoane',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de pregătire continuă pentru conducătorii auto de transport persoane.',
                ],
                [
                    'slug' => 'adr',
                    'name' => 'ADR — mărfuri periculoase',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru transportul rutier de mărfuri periculoase, cea mai numeroasă bancă ARR după consilierii de siguranță.',
                    'items' => 920,
                ],
                [
                    'slug' => 'consilier-de-siguranta',
                    'name' => 'Consilier de siguranță',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Consilier de siguranță pentru transportul mărfurilor periculoase — cea mai mare bancă de întrebări publicată de ARR.',
                    'items' => 1589,
                ],
                [
                    'slug' => 'manager-transport-marfa',
                    'name' => 'Manager de transport marfă',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de competență profesională pentru managerii de transport rutier de mărfuri.',
                ],
                [
                    'slug' => 'manager-transport-persoane',
                    'name' => 'Manager de transport persoane',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificat de competență profesională pentru managerii de transport rutier de persoane.',
                ],
                [
                    'slug' => 'taxi',
                    'name' => 'Conducător auto taxi',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru transportul în regim de taxi, inclusiv viza periodică.',
                    'items' => 240,
                ],
                [
                    'slug' => 'inchiriere',
                    'name' => 'Transport în regim de închiriere',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru transportul de persoane în regim de închiriere.',
                    'items' => 187,
                ],
                [
                    'slug' => 'instructor-auto',
                    'name' => 'Instructor auto',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru instructorii de conducere auto.',
                    'items' => 422,
                ],
                [
                    'slug' => 'profesor-legislatie-rutiera',
                    'name' => 'Profesor de legislație rutieră',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru profesorii de legislație rutieră din școlile de șoferi.',
                ],
                [
                    'slug' => 'agabaritic',
                    'name' => 'Transport agabaritic',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru transporturile care depășesc masa sau gabaritul admis.',
                    'items' => 77,
                ],
                [
                    'slug' => 'vehicule-avariate',
                    'name' => 'Transport vehicule avariate',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru transportul vehiculelor avariate sau nefuncționale.',
                ],
                [
                    'slug' => 'troleibuz',
                    'name' => 'Troleibuz',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestat pentru conducătorii de troleibuz.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function medicina(): array
    {
        return [
            'slug' => 'medicina',
            'name' => 'Medicină',
            'palette' => 'medical',
            'icon' => 'pulse',
            'authority' => 'universități de medicină și farmacie, Ministerul Sănătății, OAMGMAMR',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'De la admiterea la facultate până la rezidențiat și gradul principal. Cea mai competitivă verticală din România, cu zeci de mii de candidați în fiecare an.',
            'seo_title' => 'Grile medicină — admitere, rezidențiat, grad principal | e-test.ro',
            'seo_description' => 'Grile gratuite pentru admiterea la medicină, rezidențiat și examenul de grad principal, pe universități, specialități și sesiuni.',
            'nodes' => [
                [
                    'slug' => 'admitere',
                    'name' => 'Admitere la facultate',
                    'type' => TaxonomyNodeType::Domain,
                    'description' => 'Concursul de admitere la universitățile de medicină și farmacie, cu grile și bareme pe universitate și sesiune.',
                    'source_url' => 'https://umfcd.ro/general/grile-concurs-admitere-2026/',
                    'children' => [
                        [
                            'slug' => 'umf-carol-davila',
                            'name' => 'UMF Carol Davila București',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMF Carol Davila, cu 6.916 candidați înscriși în 2026 — cel mai mare concurs medical din țară.',
                            'source_url' => 'https://umfcd.ro/general/grile-concurs-admitere-2026/',
                        ],
                        [
                            'slug' => 'umf-cluj',
                            'name' => 'UMF Iuliu Hațieganu Cluj',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMF Cluj, cu grile corecte publicate pe variante pentru Medicină, Medicină Dentară și Farmacie.',
                            'source_url' => 'https://umfcluj.ro/programe-studii/admitere-licenta/admitere-iulie-2026/',
                        ],
                        [
                            'slug' => 'umf-iasi',
                            'name' => 'UMF Grigore T. Popa Iași',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMF Iași, prin probă scrisă tip grilă.',
                            'source_url' => 'https://www.umfiasi.ro/',
                        ],
                        [
                            'slug' => 'umf-timisoara',
                            'name' => 'UMF Victor Babeș Timișoara',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMF Timișoara, care publică subiectele și baremul de corectură pentru fiecare sesiune.',
                            'source_url' => 'https://www.umft.ro/ro/admitere-ciclul-licenta-2026/',
                        ],
                        [
                            'slug' => 'umfst-targu-mures',
                            'name' => 'UMFST Târgu Mureș',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMFST Târgu Mureș: 100 de întrebări la prima vedere, Biologie sau Chimie, cu una sau două variante corecte.',
                            'source_url' => 'https://adminfo.umfst.ro/criterii-de-admitere-2026/',
                        ],
                        [
                            'slug' => 'umf-craiova',
                            'name' => 'UMF Craiova',
                            'type' => TaxonomyNodeType::Exam,
                            'description' => 'Admiterea la UMF Craiova, cu tematică, bibliografie și grile de răspunsuri publicate oficial.',
                            'source_url' => 'https://www.umfcv.ro/ro/admitere/admitere-licenta-2026/tematica-si-bibliografia-concurs-admitere-2026',
                        ],
                    ],
                ],
                [
                    'slug' => 'rezidentiat',
                    'name' => 'Rezidențiat',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul național de rezidențiat: 200 de întrebări în 4 ore, peste 10.000 de candidați într-o sesiune.',
                    'source_url' => 'https://rezidentiat.umfcd.ro/afisares/index/rezidentiat',
                    'children' => [
                        [
                            'slug' => 'medicina',
                            'name' => 'Rezidențiat Medicină',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Domeniul cu cei mai mulți candidați la rezidențiat — 6.956 în sesiunea 2025.',
                        ],
                        [
                            'slug' => 'medicina-dentara',
                            'name' => 'Rezidențiat Medicină Dentară',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Rezidențiatul pentru medicii dentiști, cu tematică și bibliografie proprii.',
                        ],
                        [
                            'slug' => 'farmacie',
                            'name' => 'Rezidențiat Farmacie',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Rezidențiatul pentru farmaciști, organizat în aceeași sesiune națională.',
                        ],
                    ],
                ],
                [
                    'slug' => 'grad-principal',
                    'name' => 'Grad principal asistenți medicali',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul OAMGMAMR de grad principal, cu teste-grilă și grile de corectură publicate pe specialitate.',
                    'source_url' => 'https://www.oamr.ro/teste-grila-grad-sesiunea2026/',
                    'items' => 900,
                    'children' => [
                        ['slug' => 'asistenta-medicala-generala', 'name' => 'Asistență medicală generală', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'balneofizioterapie', 'name' => 'Balneofizioterapie', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'farmacie', 'name' => 'Farmacie', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'igiena-si-sanatate-publica', 'name' => 'Igienă și sănătate publică', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'laborator', 'name' => 'Laborator', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'moasa', 'name' => 'Moașă', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'nutritie-si-dietetica', 'name' => 'Nutriție și dietetică', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'radiologie', 'name' => 'Radiologie', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                        ['slug' => 'stomatologie', 'name' => 'Stomatologie', 'type' => TaxonomyNodeType::Subject, 'items' => 100],
                    ],
                ],
                [
                    'slug' => 'dietetician-autorizat',
                    'name' => 'Dietetician autorizat',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul național de dietetician autorizat: 50 de întrebări în 90 de minute, dintr-o bancă publicată de Colegiul Dieteticienilor.',
                    'source_url' => 'https://www.colegiuldieteticienilor.ro/metodologia-examenului-national-de-dietetician-autorizat/',
                    'items' => 263,
                ],
                [
                    'slug' => 'fizioterapie',
                    'name' => 'Fizioterapie',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Autorizarea profesională în fizioterapie, pe baza cadrului și metodologiei oficiale.',
                    'source_url' => 'https://legislatie.just.ro/Public/DetaliiDocument/306348',
                    'rights' => self::RIGHTS_ORIGINAL,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function drept(): array
    {
        return [
            'slug' => 'drept',
            'name' => 'Drept',
            'palette' => 'legal',
            'icon' => 'scales',
            'authority' => 'INPPA / UNBR, CSM, universități',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Admitere la Drept, examenul de intrare în Barou, INM, Școala Națională de Grefieri și notariat. Fiecare întrebare are nevoie de versionare: o grilă din 2021 poate avea alt răspuns azi.',
            'seo_title' => 'Grile drept — admitere, Barou, INM, grefieri | e-test.ro',
            'seo_description' => 'Grile gratuite pentru admiterea la Drept, examenul de Barou, INM, SNG și notar stagiar, cu trimitere la textul de lege în vigoare.',
            'nodes' => [
                [
                    'slug' => 'admitere-unibuc',
                    'name' => 'Admitere Drept — Universitatea din București',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul de admitere la Facultatea de Drept a Universității din București: Limba română, Economie și Gândire critică. 1.756 de candidați în 2026.',
                    'source_url' => 'https://drept.unibuc.ro/subiecte-de-la-concursurile-anterioare-s103-ro.htm',
                ],
                [
                    'slug' => 'admitere-ubb',
                    'name' => 'Admitere Drept — UBB Cluj',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Facultatea de Drept a UBB Cluj: probă de raționament logic, o oră, cu barem și răspunsuri publicate pe grile.',
                    'source_url' => 'https://law.ubbcluj.ro/admitere/admitere-licenta/',
                ],
                [
                    'slug' => 'barou',
                    'name' => 'Examen de Barou',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul de primire în profesia de avocat: organizarea profesiei, drept civil, procedură civilă, drept penal și procedură penală.',
                    'source_url' => 'https://inppa.ro/examene/',
                    'children' => [
                        [
                            'slug' => 'stagiari',
                            'name' => 'Avocat stagiar',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Proba scrisă tip grilă pentru obținerea titlului de avocat stagiar.',
                        ],
                        [
                            'slug' => 'definitivi',
                            'name' => 'Avocat definitiv',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Proba scrisă tip grilă pentru avocații definitivi.',
                        ],
                    ],
                ],
                [
                    'slug' => 'inm',
                    'name' => 'INM — Institutul Național al Magistraturii',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul de admitere la INM, cu tematică, bibliografie, teste-grilă și bareme publicate de CSM pentru fiecare sesiune.',
                    'source_url' => 'https://csm1909.ro/PageDetails/12817/',
                ],
                [
                    'slug' => 'sng',
                    'name' => 'Școala Națională de Grefieri',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul de admitere la SNG — una dintre cele mai curate arhive oficiale: subiecte, barem și contestații în același loc.',
                    'source_url' => 'https://www.csm1909.ro/PageDetails/12636/',
                ],
                [
                    'slug' => 'notar-stagiar',
                    'name' => 'Notar stagiar',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul pentru dobândirea calității de notar stagiar: 100 de întrebări din drept civil, procedură civilă, procedură și legislație notarială.',
                    'source_url' => 'https://www.institutulnotarial.ro/site/concurs-notari-stagiari-2026/',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function administratie(): array
    {
        return [
            'slug' => 'administratie',
            'name' => 'Administrație publică',
            'palette' => 'civic',
            'icon' => 'briefcase',
            'authority' => 'ANFP — Agenția Națională a Funcționarilor Publici',
            'source_url' => 'https://concurs-national.anfp.gov.ro/materiale-utile/teste-antrenament/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Concursul național pentru ocuparea funcțiilor publice. ANFP publică baterii de teste de antrenament cu rezolvări, pe fiecare componentă a probei de testare preliminară.',
            'seo_title' => 'Teste ANFP — concurs național funcționari publici | e-test.ro',
            'seo_description' => 'Teste grilă gratuite pentru concursul național ANFP: administrație publică, drepturi fundamentale, nediscriminare și egalitate de șanse.',
            'nodes' => [
                [
                    'slug' => 'concurs-national',
                    'name' => 'Concursul național ANFP',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Proba de testare preliminară din concursul național pentru funcția publică.',
                    'children' => [
                        ['slug' => 'administratie-publica', 'name' => 'Administrație publică', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'demnitate-umana', 'name' => 'Respectarea demnității umane', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'drepturi-si-libertati', 'name' => 'Drepturi și libertăți fundamentale', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'nediscriminare', 'name' => 'Prevenirea și combaterea discriminării', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'egalitate-de-sanse', 'name' => 'Egalitatea de șanse și tratament', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function radio(): array
    {
        return [
            'slug' => 'radio',
            'name' => 'Radiocomunicații',
            'palette' => 'signal',
            'icon' => 'antenna',
            'authority' => 'ANCOM',
            'source_url' => 'https://ancom.ro/category/autorizare-ro/radioamatori/',
            'rights' => self::RIGHTS_EXPLICIT,
            'description' => 'Autorizarea radioamatorilor de către ANCOM. Singura sursă din catalog care permite explicit reproducerea conținutului, cu indicarea sursei.',
            'seo_title' => 'Examen radioamator ANCOM — întrebări oficiale gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul de radioamator ANCOM: radiotehnică, electronică și reglementări, din subiectele oficiale publicate.',
            'nodes' => [
                [
                    'slug' => 'radioamator',
                    'name' => 'Certificat de radioamator',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul ANCOM pentru obținerea certificatului de radioamator, pe clase.',
                    'children' => [
                        [
                            'slug' => 'radiotehnica-si-electronica',
                            'name' => 'Radiotehnică și electronică',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Proba de electronică și radiotehnică pentru clasele III și II, din subiectele oficiale ANCOM, versiunea 2017-02-09.',
                            'source_url' => 'https://www.ancom.ro/wp-content/uploads/2010/07/subiecte_radiotehnica_09_02_2017.pdf',
                            'items' => 632,
                            'rights' => self::RIGHTS_EXPLICIT,
                        ],
                        [
                            'slug' => 'reglementari',
                            'name' => 'Reglementări radio',
                            'type' => TaxonomyNodeType::Subject,
                            'description' => 'Reglementările naționale și internaționale din programa de examen ANCOM.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function vanatoare(): array
    {
        return [
            'slug' => 'vanatoare',
            'name' => 'Vânătoare',
            'palette' => 'nature',
            'icon' => 'target',
            'authority' => 'Ministerul Mediului, Apelor și Pădurilor',
            'source_url' => 'https://mmediu.ro/domenii/mediu/management-cinegetic/permis-de-vanatoare/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Examenul pentru permisul de vânător, pe setul oficial de întrebări și răspunsuri actualizat conform Legii nr. 171/2022.',
            'seo_title' => 'Examen permis de vânător — 1.000 de întrebări oficiale | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul de vânător: legislație cinegetică, etică, arme și muniții, biologia vânatului.',
            'nodes' => [
                [
                    'slug' => 'permis-de-vanator',
                    'name' => 'Permis de vânător',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Setul oficial de 1.000 de întrebări cu trei variante și un singur răspuns corect.',
                    'items' => 1000,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function radioprotectie(): array
    {
        return [
            'slug' => 'radioprotectie',
            'name' => 'Radioprotecție',
            'palette' => 'medical',
            'icon' => 'radiation',
            'authority' => 'CNCAN — Comisia Națională pentru Controlul Activităților Nucleare',
            'source_url' => 'https://www.cncan.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Permisele de exercitare CNCAN, pe 21 de specialități. Cea mai mare colecție măsurată din catalog: 13.673 de itemi bruți, cu răspunsuri comentate.',
            'seo_title' => 'Permis de exercitare CNCAN — teste pe specialități | e-test.ro',
            'seo_description' => 'Pregătire pentru permisele de exercitare CNCAN: radiologie, medicină nucleară, radioterapie, control nedistructiv și celelalte specialități.',
            'nodes' => [
                [
                    'slug' => 'permise-de-exercitare',
                    'name' => 'Permise de exercitare',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul CNCAN pentru permisul de exercitare, organizat pe specialități, fiecare cu bancă proprie și răspunsuri comentate.',
                    'items' => 13673,
                    'children' => [
                        ['slug' => 'analize-fizice', 'name' => 'Analize fizice', 'type' => TaxonomyNodeType::Subject, 'items' => 603],
                        ['slug' => 'acceleratori-de-particule', 'name' => 'Acceleratori de particule', 'type' => TaxonomyNodeType::Subject, 'items' => 566],
                        ['slug' => 'curieterapie', 'name' => 'Curieterapie și brahiterapie', 'type' => TaxonomyNodeType::Subject, 'items' => 767],
                        ['slug' => 'medicina-nucleara', 'name' => 'Medicină nucleară', 'type' => TaxonomyNodeType::Subject, 'items' => 752],
                        ['slug' => 'masurari-si-surse', 'name' => 'Măsurări și surse', 'type' => TaxonomyNodeType::Subject, 'items' => 769],
                        ['slug' => 'instalatii-industriale-surse-inchise', 'name' => 'Instalații industriale cu surse închise', 'type' => TaxonomyNodeType::Subject, 'items' => 809],
                        ['slug' => 'instalatii-medicale-surse-inchise', 'name' => 'Instalații medicale cu surse închise', 'type' => TaxonomyNodeType::Subject, 'items' => 845],
                        ['slug' => 'generatori-rx-industriali', 'name' => 'Generatori RX industriali', 'type' => TaxonomyNodeType::Subject, 'items' => 603],
                        ['slug' => 'generatori-rx-medicali', 'name' => 'Generatori RX medicali', 'type' => TaxonomyNodeType::Subject, 'items' => 904],
                        ['slug' => 'marcari-si-surse-deschise', 'name' => 'Marcări și surse deschise', 'type' => TaxonomyNodeType::Subject, 'items' => 676],
                        ['slug' => 'radiochimie', 'name' => 'Radiochimie', 'type' => TaxonomyNodeType::Subject, 'items' => 675],
                        ['slug' => 'radiologie-interventionala', 'name' => 'Radiologie intervențională', 'type' => TaxonomyNodeType::Subject, 'items' => 638],
                        ['slug' => 'rontgendiagnostic', 'name' => 'Röntgendiagnostic', 'type' => TaxonomyNodeType::Subject, 'items' => 777],
                        ['slug' => 'rontgendiagnostic-dentar', 'name' => 'Röntgendiagnostic dentar', 'type' => TaxonomyNodeType::Subject, 'items' => 483],
                        ['slug' => 'ftiziologie', 'name' => 'Ftiziologie', 'type' => TaxonomyNodeType::Subject, 'items' => 583],
                        ['slug' => 'rontgenterapie', 'name' => 'Röntgenterapie', 'type' => TaxonomyNodeType::Subject, 'items' => 484],
                        ['slug' => 'control-bagaje-surse-inchise', 'name' => 'Control bagaje cu surse închise', 'type' => TaxonomyNodeType::Subject, 'items' => 347],
                        ['slug' => 'control-bagaje-generatori-rx', 'name' => 'Control bagaje cu generatori RX', 'type' => TaxonomyNodeType::Subject, 'items' => 341],
                        ['slug' => 'teleterapie', 'name' => 'Teleterapie și acceleratori', 'type' => TaxonomyNodeType::Subject, 'items' => 778],
                        ['slug' => 'terapie-cu-surse-deschise', 'name' => 'Terapie cu surse deschise', 'type' => TaxonomyNodeType::Subject, 'items' => 676],
                        ['slug' => 'radiologie-veterinara', 'name' => 'Radiologie veterinară', 'type' => TaxonomyNodeType::Subject, 'items' => 597],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function finante(): array
    {
        return [
            'slug' => 'finante',
            'name' => 'Finanțe și audit',
            'palette' => 'finance',
            'icon' => 'chart',
            'authority' => 'CAFR — Camera Auditorilor Financiari din România',
            'source_url' => 'https://cafr.ro/examen-de-competenta-profesionala-2026/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Examenul de competență profesională pentru auditori financiari, cu subiecte și rezolvări publicate pentru sesiunile 2021–2025.',
            'seo_title' => 'Examen auditor financiar CAFR — grile gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul CAFR de competență profesională: grile din sesiunile anterioare, cu rezolvări.',
            'nodes' => [
                [
                    'slug' => 'auditor-financiar',
                    'name' => 'Auditor financiar',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul de competență profesională: probă teoretică cu grile și sinteză, plus probă practică de aplicare.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fiscal(): array
    {
        return [
            'slug' => 'fiscal',
            'name' => 'Fiscalitate',
            'palette' => 'finance',
            'icon' => 'receipt',
            'authority' => 'CCF — Camera Consultanților Fiscali',
            'source_url' => 'https://www.ccfiscali.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Examenul de consultant fiscal: 40 de întrebări, patru variante, un singur răspuns corect, prag 75 din 100. Fiecare întrebare are nevoie de anul și actele normative pe care se bazează.',
            'seo_title' => 'Examen consultant fiscal CCF — grile gratuite | e-test.ro',
            'seo_description' => 'Grile gratuite pentru examenul de consultant fiscal și consultant fiscal asistent, cu trimitere la legislația în vigoare la data întrebării.',
            'nodes' => [
                [
                    'slug' => 'consultant-fiscal',
                    'name' => 'Consultant fiscal',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul de atribuire a calității de consultant fiscal.',
                ],
                [
                    'slug' => 'consultant-fiscal-asistent',
                    'name' => 'Consultant fiscal asistent',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul pentru consultant fiscal asistent, cu chestionar și grilă de corectare publicate.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function insolventa(): array
    {
        return [
            'slug' => 'insolventa',
            'name' => 'Insolvență',
            'palette' => 'legal',
            'icon' => 'gavel',
            'authority' => 'UNPIR — Uniunea Națională a Practicienilor în Insolvență din România',
            'source_url' => 'https://www.unpir.ro/examen-pentru-dobandirea-calitatii-de-practician',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Examenul de acces în profesia de practician în insolvență, pe tematica și modelele de chestionar publicate de UNPIR.',
            'seo_title' => 'Examen practician în insolvență UNPIR — grile | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul UNPIR de acces în profesia de practician în insolvență: drept, contabilitate și fiscalitate.',
            'nodes' => [
                [
                    'slug' => 'practician-in-insolventa',
                    'name' => 'Practician în insolvență',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul de acces în profesie, cu model de grilă și model de chestionar puse la dispoziție oficial.',
                    'children' => [
                        ['slug' => 'drept', 'name' => 'Drept', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'contabilitate-si-fiscalitate', 'name' => 'Contabilitate și fiscalitate', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function energie(): array
    {
        return [
            'slug' => 'energie',
            'name' => 'Energie',
            'palette' => 'energy',
            'icon' => 'bolt',
            'authority' => 'ANRE — Autoritatea Națională de Reglementare în domeniul Energiei',
            'source_url' => 'https://anre.ro/participanti-la-piata-de-energie/persoane-fizice/energie-electrica/electricieni/examene/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Autorizarea electricienilor și a instalatorilor de gaze naturale. ANRE publică tematica, bibliografia și exemple de întrebări, pe grade de autorizare.',
            'seo_title' => 'Examen ANRE electrician autorizat — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenele ANRE de autorizare a electricienilor și a instalatorilor de gaze naturale, pe grade.',
            'nodes' => [
                [
                    'slug' => 'electrician-autorizat',
                    'name' => 'Electrician autorizat',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul ANRE de autorizare a electricienilor, organizat de regulă de două ori pe an.',
                    'children' => [
                        ['slug' => 'electrotehnica', 'name' => 'Electrotehnică', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'legislatie-energie', 'name' => 'Legislație în energie', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
                [
                    'slug' => 'gaze-naturale',
                    'name' => 'Instalator gaze naturale',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Autorizarea persoanelor fizice în domeniul gazelor naturale.',
                    'source_url' => 'https://anre.ro/participanti-la-piata-de-energie/persoane-fizice/gaze-naturale/',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function ordinePublica(): array
    {
        return [
            'slug' => 'ordine-publica',
            'name' => 'Ordine publică',
            'palette' => 'civic',
            'icon' => 'shield',
            'authority' => 'MAI, Academia de Poliție, MApN',
            'source_url' => 'https://admitere.academiadepolitie.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Admiterea la Academia de Poliție, la școlile MAI și la colegiile militare, plus concursurile de ocupare a posturilor unde subiectele și baremul se publică după probă.',
            'seo_title' => 'Admitere Academia de Poliție și școli MAI — teste | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru admiterea la Academia de Poliție, Facultatea de Pompieri, școlile postliceale MAI și colegiile militare.',
            'nodes' => [
                [
                    'slug' => 'academia-de-politie',
                    'name' => 'Academia de Poliție',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Facultatea de Poliție, cu subiecte și bareme publicate după concurs.',
                ],
                [
                    'slug' => 'facultatea-de-pompieri',
                    'name' => 'Facultatea de Pompieri',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Facultatea de Pompieri din cadrul Academiei de Poliție.',
                ],
                [
                    'slug' => 'scoli-postliceale-mai',
                    'name' => 'Școli postliceale MAI',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la școlile de agenți de poliție și la celelalte școli postliceale din subordinea MAI.',
                    'source_url' => 'https://hub.mai.gov.ro/',
                ],
                [
                    'slug' => 'colegii-militare',
                    'name' => 'Colegii militare',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la colegiile naționale militare.',
                    'source_url' => 'https://dgmru.mapn.ro/pages/colegii-militare',
                ],
                [
                    'slug' => 'detectiv-particular',
                    'name' => 'Detectiv particular',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul pentru atestarea calității de detectiv particular, organizat periodic de Poliția Română.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function drone(): array
    {
        return [
            'slug' => 'drone',
            'name' => 'Drone',
            'palette' => 'air',
            'icon' => 'drone',
            'authority' => 'AACR — Autoritatea Aeronautică Civilă Română',
            'source_url' => 'https://www.caa.ro/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Examinarea online pentru operatorii de drone: A1/A3 cu 40 de întrebări, A2 cu 30, prag de promovare 75%. Conținut original construit din materialele oficiale AACR și regulamentele EASA.',
            'seo_title' => 'Examen drone A1/A3 și A2 — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenele AACR de operator drone: categoriile deschise A1/A3 și A2 și scenariile standard STS.',
            'nodes' => [
                [
                    'slug' => 'a1-a3',
                    'name' => 'Categoria A1/A3',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul online pentru categoria deschisă A1/A3: 40 de întrebări, minimum 75% pentru promovare.',
                ],
                [
                    'slug' => 'a2',
                    'name' => 'Categoria A2',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul pentru categoria deschisă A2: 30 de întrebări, minimum 75% pentru promovare.',
                ],
                [
                    'slug' => 'sts',
                    'name' => 'Scenarii standard STS',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examinarea cunoștințelor teoretice pentru operațiuni UAS în cadrul scenariilor standard, din categoria specifică.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tehnic(): array
    {
        return [
            'slug' => 'tehnic',
            'name' => 'Tehnic și industrial',
            'palette' => 'it',
            'icon' => 'wrench',
            'authority' => 'ISCIR, RAR, organisme de certificare',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Autorizări tehnice reglementate: operator RSVTI, inspecție tehnică periodică, tahografe și gaze fluorurate. Multe ocupații, fiecare cu programa ei.',
            'seo_title' => 'Autorizări tehnice — ISCIR, RAR, F-Gas | e-test.ro',
            'seo_description' => 'Teste gratuite pentru autorizările tehnice: operator RSVTI, ITP, tahografe, conducător de atelier și certificare F-Gas.',
            'nodes' => [
                [
                    'slug' => 'iscir-rsvti',
                    'name' => 'Operator RSVTI',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul ISCIR pentru operator responsabil cu supravegherea și verificarea tehnică a instalațiilor.',
                    'source_url' => 'https://iscir.ro/exemple-intrebari-teste-operator-rsvti',
                ],
                [
                    'slug' => 'rar-itp',
                    'name' => 'Inspecție tehnică periodică',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestarea personalului care efectuează inspecția tehnică periodică a vehiculelor.',
                    'source_url' => 'https://www.rarom.ro/?page_id=1340',
                ],
                [
                    'slug' => 'rar-tahograf',
                    'name' => 'Tahografe',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificarea tehnicienilor pentru montarea și verificarea tahografelor.',
                    'source_url' => 'https://www.rarom.ro/?page_id=298579',
                ],
                [
                    'slug' => 'rar-conducator-atelier',
                    'name' => 'Conducător de atelier',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestarea conducătorilor de atelier auto autorizați RAR.',
                    'source_url' => 'https://www.rarom.ro/?page_id=774',
                ],
                [
                    'slug' => 'f-gas',
                    'name' => 'Gaze fluorurate (F-Gas)',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificarea personalului care lucrează cu gaze fluorurate cu efect de seră.',
                    'source_url' => 'https://www.agfro.ro/',
                    'rights' => self::RIGHTS_ORIGINAL,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function constructii(): array
    {
        return [
            'slug' => 'constructii',
            'name' => 'Construcții',
            'palette' => 'energy',
            'icon' => 'building',
            'authority' => 'ISC — Inspectoratul de Stat în Construcții, MDLPA',
            'source_url' => 'https://isc.gov.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Autorizările din construcții: diriginte de șantier, responsabil tehnic cu execuția, auditor energetic, verificator de proiecte și expert tehnic. Conținut versionat legislativ.',
            'seo_title' => 'Autorizări construcții — diriginte de șantier, RTE | e-test.ro',
            'seo_description' => 'Teste gratuite pentru autorizările din construcții: diriginte de șantier, RTE, auditor energetic, verificator de proiecte și expert tehnic.',
            'nodes' => [
                [
                    'slug' => 'diriginte-de-santier',
                    'name' => 'Diriginte de șantier',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Autorizarea diriginților de șantier, pe domenii și categorii de construcții.',
                    'source_url' => 'https://isc.gov.ro/files/2016/Autorizari/procedura%20diriginti%20de%20santier%202011.pdf',
                ],
                [
                    'slug' => 'responsabil-tehnic-cu-executia',
                    'name' => 'Responsabil tehnic cu execuția',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Autorizarea RTE, cu sesiuni organizate anual de ISC.',
                    'source_url' => 'https://isc.gov.ro/autorizare_rte_sesiuni_2026.html',
                ],
                [
                    'slug' => 'auditor-energetic',
                    'name' => 'Auditor energetic pentru clădiri',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestarea auditorilor energetici pentru clădiri, pe grade și specialități.',
                    'source_url' => 'https://legislatie.just.ro/Public/DetaliiDocumentAfis/309527',
                ],
                [
                    'slug' => 'verificator-de-proiecte',
                    'name' => 'Verificator de proiecte',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestarea verificatorilor de proiecte, pe cerințe esențiale de calitate.',
                ],
                [
                    'slug' => 'expert-tehnic',
                    'name' => 'Expert tehnic',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Atestarea experților tehnici în construcții.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function naval(): array
    {
        return [
            'slug' => 'naval',
            'name' => 'Naval',
            'palette' => 'air',
            'icon' => 'anchor',
            'authority' => 'ANR — Autoritatea Navală Română',
            'source_url' => 'https://portal.rna.ro/',
            'rights' => self::RIGHTS_RESTRICTED,
            'description' => 'Permisul de conducător de ambarcațiune de agrement și examinarea personalului navigant maritim și fluvial, pe baza COLREG, a regulamentului de navigație pe Dunăre și a bibliografiei oficiale.',
            'seo_title' => 'Permis ambarcațiune de agrement — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru permisul de conducător de ambarcațiune de agrement: COLREG, navigație pe Dunăre și reguli de siguranță.',
            'nodes' => [
                [
                    'slug' => 'permis-ambarcatiune-agrement',
                    'name' => 'Permis ambarcațiune de agrement',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul CAA pentru conducătorii de ambarcațiuni de agrement.',
                    'children' => [
                        ['slug' => 'clasa-c', 'name' => 'Clasa C', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'clasa-d', 'name' => 'Clasa D', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
                [
                    'slug' => 'personal-navigant-maritim',
                    'name' => 'Personal navigant maritim',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examinarea personalului navigant maritim, conform fluxurilor oficiale ANR.',
                    'source_url' => 'https://portal.rna.ro/personal-navigant/maritim/examinare-personal-navigant-maritim/',
                ],
                [
                    'slug' => 'personal-navigant-fluvial',
                    'name' => 'Personal navigant fluvial',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examinarea personalului navigant fluvial.',
                    'source_url' => 'https://portal.rna.ro/personal-navigant/fluvial/examinare-personal-navigant-fluvial/',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function feroviar(): array
    {
        return [
            'slug' => 'feroviar',
            'name' => 'Feroviar',
            'palette' => 'it',
            'icon' => 'train',
            'authority' => 'CENAFER — Centrul Național de Calificare și Instruire Feroviară',
            'source_url' => 'https://www.cenafer.ro/',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Calificarea și autorizarea personalului feroviar, pe funcțiile examinate de CENAFER.',
            'seo_title' => 'Examene feroviare CENAFER — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenele CENAFER de calificare și autorizare a personalului feroviar.',
            'nodes' => [
                [
                    'slug' => 'personal-feroviar',
                    'name' => 'Personal feroviar',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examinările organizate de CENAFER pentru funcțiile din siguranța circulației.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function evaluare(): array
    {
        return [
            'slug' => 'evaluare',
            'name' => 'Evaluare bunuri',
            'palette' => 'finance',
            'icon' => 'gauge',
            'authority' => 'ANEVAR',
            'source_url' => 'https://www.anevar.ro/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Examenul de membru stagiar ANEVAR: test grilă cu 100 de întrebări selectate dintr-o bază de minimum 500, prag 70 de puncte, inclusiv logică și gramatică.',
            'seo_title' => 'Examen membru stagiar ANEVAR — grile gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul ANEVAR de membru stagiar: bibliografia de evaluare, logică și gramatică.',
            'nodes' => [
                [
                    'slug' => 'membru-stagiar',
                    'name' => 'Membru stagiar ANEVAR',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Testul grilă de 100 de întrebări pentru dobândirea calității de membru stagiar.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function asigurari(): array
    {
        return [
            'slug' => 'asigurari',
            'name' => 'Asigurări și piețe financiare',
            'palette' => 'finance',
            'icon' => 'umbrella',
            'authority' => 'ISF — Institutul de Studii Financiare',
            'source_url' => 'https://www.isf.ro/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Certificarea în distribuția de asigurări și pe piața de capital: test grilă de 40 de întrebări în 40 de minute, cu prag de 28 de răspunsuri corecte.',
            'seo_title' => 'Certificare ISF distribuție asigurări — teste | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru certificarea ISF în distribuția de asigurări și pentru certificarea pe piața de capital.',
            'nodes' => [
                [
                    'slug' => 'distributie-asigurari',
                    'name' => 'Distribuție de asigurări',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul de certificare pentru distribuitorii de asigurări.',
                ],
                [
                    'slug' => 'piata-de-capital',
                    'name' => 'Piața de capital',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Certificarea profesională pentru piața de capital.',
                    'source_url' => 'https://www.isf.ro/ro/certificare-piata-de-capital',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cadastru(): array
    {
        return [
            'slug' => 'cadastru',
            'name' => 'Cadastru',
            'palette' => 'energy',
            'icon' => 'map',
            'authority' => 'ANCPI — Agenția Națională de Cadastru și Publicitate Imobiliară',
            'source_url' => 'https://legislatie.just.ro/Public/DetaliiDocument/117871',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Autorizarea persoanelor care execută lucrări de cadastru, geodezie și cartografie, pe categoriile din regulamentul ANCPI.',
            'seo_title' => 'Autorizare ANCPI cadastru — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul de autorizare ANCPI în cadastru, geodezie și cartografie.',
            'nodes' => [
                [
                    'slug' => 'autorizare-ancpi',
                    'name' => 'Autorizare ANCPI',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Examenul de autorizare pentru executarea lucrărilor de cadastru, geodezie și cartografie.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function agricultura(): array
    {
        return [
            'slug' => 'agricultura',
            'name' => 'Agricultură',
            'palette' => 'nature',
            'icon' => 'leaf',
            'authority' => 'ANF — Autoritatea Națională Fitosanitară',
            'source_url' => 'https://anfdf.ro/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Instruirea obligatorie a utilizatorilor profesioniști de produse de protecție a plantelor și verificarea cunoștințelor la final.',
            'seo_title' => 'Atestat produse fitosanitare — teste gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru atestatul de utilizator profesionist de produse de protecție a plantelor.',
            'nodes' => [
                [
                    'slug' => 'produse-fitosanitare',
                    'name' => 'Produse de protecție a plantelor',
                    'type' => TaxonomyNodeType::Certification,
                    'description' => 'Verificarea cunoștințelor după instruirea obligatorie pentru utilizatorii profesioniști.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function proprietateIntelectuala(): array
    {
        return [
            'slug' => 'proprietate-intelectuala',
            'name' => 'Proprietate intelectuală',
            'palette' => 'signal',
            'icon' => 'lightbulb',
            'authority' => 'OSIM — Oficiul de Stat pentru Invenții și Mărci',
            'source_url' => 'https://www.osim.ro/consilieri-in-proprietate-industriala',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Examenul pentru dobândirea calității de consilier în proprietate industrială — o nișă mică, dar cu foarte puțină pregătire disponibilă online.',
            'seo_title' => 'Consilier în proprietate industrială — teste | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul OSIM de consilier în proprietate industrială: invenții, mărci, desene și modele.',
            'nodes' => [
                [
                    'slug' => 'consilier-osim',
                    'name' => 'Consilier în proprietate industrială',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul organizat pentru înscrierea în registrul consilierilor în proprietate industrială.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function contabilitate(): array
    {
        return [
            'slug' => 'contabilitate',
            'name' => 'Contabilitate',
            'palette' => 'finance',
            'icon' => 'calculator',
            'authority' => 'CECCAR',
            'source_url' => 'https://ceccar.ro/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Examenul de acces la profesia contabilă. Materialele de pregătire ale corpului profesional sunt comerciale, așa că pregătirea de aici se construiește pe legislație și tematică.',
            'seo_title' => 'Examen acces CECCAR — grile gratuite | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru examenul de acces la profesia contabilă: contabilitate, fiscalitate, drept și audit.',
            'nodes' => [
                [
                    'slug' => 'acces-la-profesie',
                    'name' => 'Acces la profesia contabilă',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul de acces pentru stagiul de expert contabil și contabil autorizat.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function admitere(): array
    {
        return [
            'slug' => 'admitere',
            'name' => 'Admitere la facultate',
            'palette' => 'lang',
            'icon' => 'cap',
            'authority' => 'universități de stat',
            'rights' => self::RIGHTS_UNCLEAR,
            'description' => 'Concursurile de admitere de la facultățile care păstrează probă scrisă: psihologie, informatică, economie și politehnică. Admiterea la medicină și la drept are verticala ei.',
            'seo_title' => 'Admitere la facultate — teste și subiecte rezolvate | e-test.ro',
            'seo_description' => 'Pregătire gratuită pentru admiterea la facultate: psihologie, informatică, ASE și Politehnică, din subiectele publicate oficial.',
            'nodes' => [
                [
                    'slug' => 'psihologie-unibuc',
                    'name' => 'Psihologie — Universitatea din București',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Facultatea de Psihologie și Științele Educației a Universității din București.',
                    'source_url' => 'https://fpse.unibuc.ro/studii-universitare-de-licenta/',
                ],
                [
                    'slug' => 'informatica-ubb',
                    'name' => 'Informatică — UBB Cluj',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul de admitere la Facultatea de Matematică și Informatică a UBB, cu subiectele probei scrise publicate.',
                    'source_url' => 'https://www.cs.ubbcluj.ro/',
                ],
                [
                    'slug' => 'ase',
                    'name' => 'ASE București',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Academia de Studii Economice din București.',
                    'source_url' => 'https://ase.ro/admitere/licenta-iulie-2026/',
                ],
                [
                    'slug' => 'politehnica-etti',
                    'name' => 'Politehnica București — ETTI',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Admiterea la Facultatea de Electronică, Telecomunicații și Tehnologia Informației.',
                    'source_url' => 'https://etti.upb.ro/cp_services/faza-2/',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function educatie(): array
    {
        return [
            'slug' => 'educatie',
            'name' => 'Educație',
            'palette' => 'lang',
            'icon' => 'book',
            'authority' => 'Ministerul Educației',
            'source_url' => 'https://subiecte.edu.ro/2026/',
            'rights' => self::RIGHTS_ORIGINAL,
            'description' => 'Bacalaureat, Evaluare Națională, Titularizare și Definitivat. Cea mai mare cerere din țară — peste 39.000 de candidați doar la Titularizare — pe itemi construiți după programele oficiale.',
            'seo_title' => 'Teste BAC, Evaluare Națională, Titularizare | e-test.ro',
            'seo_description' => 'Teste gratuite pentru Bacalaureat, Evaluarea Națională, Titularizare și Definitivat, construite după programele și competențele oficiale.',
            'nodes' => [
                [
                    'slug' => 'bacalaureat',
                    'name' => 'Bacalaureat',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Pregătire pentru probele scrise de la Bacalaureat, pe discipline și competențe evaluate.',
                    'children' => [
                        ['slug' => 'romana', 'name' => 'Limba și literatura română', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'matematica', 'name' => 'Matematică', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'biologie', 'name' => 'Biologie', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'istorie', 'name' => 'Istorie', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'geografie', 'name' => 'Geografie', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
                [
                    'slug' => 'evaluare-nationala',
                    'name' => 'Evaluare Națională',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Pregătire pentru Evaluarea Națională de la clasa a VIII-a.',
                    'children' => [
                        ['slug' => 'romana', 'name' => 'Limba și literatura română', 'type' => TaxonomyNodeType::Subject],
                        ['slug' => 'matematica', 'name' => 'Matematică', 'type' => TaxonomyNodeType::Subject],
                    ],
                ],
                [
                    'slug' => 'titularizare',
                    'name' => 'Titularizare',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Concursul național de ocupare a posturilor didactice: 39.996 de candidați cu drept de participare în 2026.',
                    'source_url' => 'https://subiecte.edu.ro/2026/titularizare/',
                ],
                [
                    'slug' => 'definitivat',
                    'name' => 'Definitivat',
                    'type' => TaxonomyNodeType::Exam,
                    'description' => 'Examenul național de definitivare în învățământ, organizat la 106 discipline.',
                    'source_url' => 'https://subiecte.edu.ro/2026/definitivare/Subiecte_si_bareme/',
                ],
            ],
        ];
    }
}
