<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Câte greșeli închid chestionarul.
 *
 * La proba teoretică auto, examinarea nu merge până la ultima întrebare: la a
 * patra greșeală pentru categoria A sau a cincea pentru B, C și D, chestionarul
 * se închide pe loc. E o regulă de probă, nu de notare — candidatul care a
 * greșit destul ca să nu mai poată promova nu mai e ținut în sală.
 *
 * Fără ea, o simulare care se lasă rezolvată până la capăt spune o minciună
 * liniștitoare despre cum decurge examenul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_wrong_answers')->nullable()->after('passing_questions');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->dropColumn('max_wrong_answers');
        });
    }
};
