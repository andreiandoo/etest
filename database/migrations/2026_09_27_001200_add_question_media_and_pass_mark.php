<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Două lucruri de care are nevoie examenul auto și pe care platforma nu le avea.
 *
 * Imaginea întrebării: jumătate din chestionarele de legislație rutieră arată un
 * indicator sau o intersecție, iar enunțul singur nu înseamnă nimic fără ea. Stă
 * într-un câmp de sine stătător, nu în `metadata`, fiindcă e conținut afișat, nu
 * informație despre conținut: are nevoie de text alternativ, de autor și de
 * licență, iar toate trei trebuie să se vadă în administrare.
 *
 * Pragul de promovare în întrebări: la proba teoretică se cer 22 de răspunsuri
 * corecte din 26, nu 84,62%. Procentul rămâne pentru testele unde chiar așa e
 * scris pragul, dar acolo unde legea numără întrebări, le numărăm și noi —
 * altfel candidatul citește pe pagină un prag pe care nimeni nu i l-a spus
 * vreodată în felul acela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->jsonb('media')->nullable()->after('explanation');
        });

        Schema::table('tests', function (Blueprint $table): void {
            $table->unsignedSmallInteger('passing_questions')->nullable()->after('passing_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('media');
        });

        Schema::table('tests', function (Blueprint $table): void {
            $table->dropColumn('passing_questions');
        });
    }
};
