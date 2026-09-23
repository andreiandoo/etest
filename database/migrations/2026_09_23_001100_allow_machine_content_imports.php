<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un import poate veni de la o comandă, nu doar de la o persoană.
 *
 * `sources:sync` rulează din cron sau din terminal, unde nu există un
 * utilizator autentificat. Alternativa — să legăm importul de primul cont de
 * administrator găsit — ar pune în istoric numele cuiva care nu a apăsat nimic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
