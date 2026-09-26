<?php

use App\Enums\PublicationStatus;
use App\Enums\TaxonomyNodeType;
use App\Models\Question;
use App\Models\Source;
use App\Models\TaxonomyNode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use App\Services\Content\ImportPublisher;

/**
 * Publicarea de după un import se uită la locul din taxonomie, nu la adresa
 * documentului. Proba de aici e chiar cazul care a scăpat: CNCAN dă câte un PDF
 * pe specialitate, întrebările poartă adresa specialității, iar Baroul își pune
 * întrebările cu două niveluri mai jos decât secțiunea sursei.
 */
function publishFixture(): array
{
    $vertical = Vertical::factory()->create();

    $section = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'Permise de exercitare',
        'slug' => 'permise-de-exercitare',
    ]);

    $child = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'parent_id' => $section->id,
        'type' => TaxonomyNodeType::Subject,
        'name' => 'Radiografie industrială',
        'slug' => 'radiografie-industriala',
    ]);

    $grandchild = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'parent_id' => $child->id,
        'type' => TaxonomyNodeType::Subject,
        'name' => 'Radioprotecție operațională',
        'slug' => 'radioprotectie-operationala',
    ]);

    $source = Source::create([
        'key' => 'proba-publicare',
        'authority' => 'CNCAN',
        'title' => 'Proba de publicare',
        'vertical_id' => $vertical->id,
        'taxonomy_node_id' => $section->id,
        'document_url' => 'https://exemplu.ro/pagina-sursei',
        'file_format' => 'pdf',
        'rights_status' => 'official_public_unclear',
    ]);

    return [$vertical, $section, $child, $grandchild, $source];
}

function draftQuestion(Vertical $vertical, TaxonomyNode $node, string $url): Question
{
    return Question::create([
        'vertical_id' => $vertical->id,
        'taxonomy_node_id' => $node->id,
        'type' => 'single_choice',
        'status' => PublicationStatus::Draft,
        'prompt' => 'Ciornă în '.$node->slug,
        'source_url' => $url,
        'difficulty' => 2,
    ]);
}

test('a question whose address is not the source page still gets published', function () {
    [$vertical, , $child, , $source] = publishFixture();

    // Adresa specialității, nu cea a sursei: exact ce trimite CNCAN.
    $question = draftQuestion($vertical, $child, 'https://exemplu.ro/specialitatea-1.pdf');

    $published = (new ImportPublisher)->publish($source->refresh());

    expect($published['questions'])->toBe(1)
        ->and($question->refresh()->status)->toBe(PublicationStatus::Published)
        ->and($question->reviewed_at)->not->toBeNull();
});

test('questions and tests two levels below the source section are published too', function () {
    [$vertical, , , $grandchild, $source] = publishFixture();

    draftQuestion($vertical, $grandchild, 'https://exemplu.ro/oricare.pdf');

    $test = TestDefinition::factory()->for($vertical)->create([
        'taxonomy_node_id' => $grandchild->id,
        'status' => PublicationStatus::Draft,
        'published_at' => null,
    ]);

    $published = (new ImportPublisher)->publish($source->refresh());

    expect($published['questions'])->toBe(1)
        ->and($published['tests'])->toBe(1)
        ->and($test->refresh()->status)->toBe(PublicationStatus::Published)
        ->and($test->published_at)->not->toBeNull();
});

test('what sits outside the source section is left alone', function () {
    [$vertical, , , , $source] = publishFixture();

    $other = TaxonomyNode::create([
        'vertical_id' => $vertical->id,
        'type' => TaxonomyNodeType::Exam,
        'name' => 'Altă secțiune',
        'slug' => 'alta-sectiune',
    ]);

    $outside = draftQuestion($vertical, $other, 'https://exemplu.ro/pagina-sursei');
    $loose = Question::create([
        'vertical_id' => $vertical->id,
        'type' => 'single_choice',
        'status' => PublicationStatus::Draft,
        'prompt' => 'Fără secțiune',
        'difficulty' => 2,
    ]);

    $published = (new ImportPublisher)->publish($source->refresh());

    expect($published['questions'])->toBe(0)
        ->and($outside->refresh()->status)->toBe(PublicationStatus::Draft)
        ->and($loose->refresh()->status)->toBe(PublicationStatus::Draft);
});

test('a source without a section publishes nothing at all', function () {
    [$vertical, , $child] = publishFixture();

    draftQuestion($vertical, $child, 'https://exemplu.ro/oricare.pdf');

    $orphan = Source::create([
        'key' => 'proba-fara-sectiune',
        'authority' => 'CNCAN',
        'title' => 'Sursă fără secțiune',
        'vertical_id' => $vertical->id,
        'document_url' => 'https://exemplu.ro/pagina-sursei',
        'file_format' => 'pdf',
        'rights_status' => 'official_public_unclear',
    ]);

    expect((new ImportPublisher)->publish($orphan))->toBe(['questions' => 0, 'tests' => 0]);
});
