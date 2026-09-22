<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxonomy_nodes', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('description');
            $table->text('seo_description')->nullable()->after('seo_title');
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('description');
            $table->text('seo_description')->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });

        Schema::table('taxonomy_nodes', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
