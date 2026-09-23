<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Catalogul de examene e date de referință, nu conținut demonstrativ, deci
     * rulează la fiecare `db:seed`. Verticalele apar inactive și devin publice
     * abia când au conținut.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
    }
}
