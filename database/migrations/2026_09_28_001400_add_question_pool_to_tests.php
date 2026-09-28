<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Locul din care își trage un test întrebările, când nu are listă fixă.
 *
 * La chestionarele auto nu există „testul numărul 7”: există proba, iar
 * întrebările se aleg la fiecare accesare, din tot ce e publicat în secțiunea
 * aceea. Un test cu listă fixă rămâne posibil și necesar — grila INM din 2024 a
 * avut chiar întrebările acelea și nu trebuie să se schimbe niciodată.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->jsonb('question_pool')->nullable()->after('question_limit');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table): void {
            $table->dropColumn('question_pool');
        });
    }
};
