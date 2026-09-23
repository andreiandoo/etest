<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slug-ul unei secțiuni devine unic față de părinte, nu față de tot domeniul.
 *
 * Catalogul real cere asta: „Farmacie” există și la rezidențiat, și la gradul
 * principal; „Română” există și la bacalaureat, și la evaluarea națională. Cu
 * unicitatea veche, a doua secțiune cu același nume ar fi fost respinsă de
 * bază, iar singura ieșire ar fi fost un slug artificial în adresă.
 *
 * Rădăcinile au nevoie de un index separat: în PostgreSQL două valori NULL nu
 * se ciocnesc niciodată într-un index unic, deci `parent_id IS NULL` ar fi
 * lăsat să treacă două secțiuni de nivel întâi cu același slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxonomy_nodes', function (Blueprint $table) {
            $table->dropUnique(['vertical_id', 'slug']);
            $table->unique(['vertical_id', 'parent_id', 'slug']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX taxonomy_nodes_vertical_root_slug_unique
             ON taxonomy_nodes (vertical_id, slug)
             WHERE parent_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS taxonomy_nodes_vertical_root_slug_unique');

        Schema::table('taxonomy_nodes', function (Blueprint $table) {
            $table->dropUnique(['vertical_id', 'parent_id', 'slug']);
            $table->unique(['vertical_id', 'slug']);
        });
    }
};
