<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setări de aplicație editabile din admin.
 *
 * Lucruri precum identificatorul Google Analytics sau al Meta Pixel nu au ce
 * căuta în .env: se schimbă de către oameni care nu au acces la server, și un
 * deploy n-ar trebui să fie necesar ca să corectezi un ID greșit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
