<?php

use App\Enums\PublicationStatus;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\TestDefinition;
use App\Models\User;
use App\Models\Vertical;

/**
 * Fiecare ecran din panou se deschide și e închis pentru cine nu e admin.
 *
 * Testul ține lista completă dinadins: o resursă nouă cu o coloană greșită sau
 * o relație inexistentă cade abia la randare, iar panoul de administrare nu e
 * atins de restul suitei. Aici se prinde înainte de deploy.
 */
function adminScreens(): array
{
    return [
        '/admin',
        '/admin/domenii',
        '/admin/taxonomie',
        '/admin/teste',
        '/admin/intrebari',
        '/admin/importuri',
        '/admin/revizuire',
        '/admin/calitate',
        '/admin/sponsori',
        '/admin/campanii-sponsorizate',
        '/admin/campanii-lead',
        '/admin/lead-uri',
        '/admin/comercianti-afiliati',
        '/admin/afiliere',
        '/admin/newsletter',
        '/admin/marca-proprie',
        '/admin/api',
        '/admin/chei-api',
        '/admin/analytics',
    ];
}

test('every admin screen renders for an administrator', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (adminScreens() as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

test('no admin screen is reachable without the admin flag', function () {
    $user = User::factory()->create(['is_admin' => false]);

    foreach (adminScreens() as $url) {
        $this->actingAs($user)->get($url)->assertForbidden();
    }
});

test('the review queue shows only content awaiting a decision', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vertical = Vertical::factory()->create();

    $waiting = TestDefinition::factory()->for($vertical)->create([
        'title' => 'Test care asteapta revizuire',
        'status' => PublicationStatus::Review,
    ]);

    $draft = TestDefinition::factory()->for($vertical)->create([
        'title' => 'Test inca in lucru',
        'status' => PublicationStatus::Draft,
    ]);

    $this->actingAs($admin)
        ->get('/admin/revizuire')
        ->assertOk()
        ->assertSee($waiting->title)
        ->assertDontSee($draft->title);
});

test('the quality queue lists reports users sent during a test', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $reporter = User::factory()->create();
    $vertical = Vertical::factory()->create();

    $question = Question::create([
        'vertical_id' => $vertical->id,
        'type' => 'single_choice',
        'status' => PublicationStatus::Draft,
        'prompt' => 'Intrebare semnalata de un utilizator',
        'difficulty' => 2,
    ]);

    QuestionReport::create([
        'user_id' => $reporter->id,
        'question_id' => $question->id,
        'reason' => 'outdated',
        'message' => 'Legislatia s-a schimbat',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get('/admin/calitate')
        ->assertOk()
        ->assertSee('Informație depășită');
});
