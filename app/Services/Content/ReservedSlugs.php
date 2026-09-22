<?php

namespace App\Services\Content;

/**
 * Slug-uri pe care o verticală nu le poate folosi.
 *
 * Verticalele stau direct în rădăcină — e-test.ro/auto — deci un slug egal cu
 * o rută fixă ar fi pur și simplu inaccesibil: ruta fixă e înregistrată prima
 * și câștigă. Fără verificarea asta, un administrator ar putea crea o
 * verticală „panou” care dă 404 fără niciun motiv vizibil.
 */
final class ReservedSlugs
{
    /**
     * @var array<int, string>
     */
    private const RESERVED = [
        'admin',
        'api',
        'autentificare',
        'bun-venit',
        'cauta',
        'catre',
        'clasament',
        'cont',
        'cont-nou',
        'iesire',
        'istoric',
        'newsletter',
        'panou',
        'rezultate',
        'sitemap',
        'sitemaps',
        'storage',
        'up',
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::RESERVED;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(mb_strtolower(trim($slug)), self::RESERVED, true);
    }
}
