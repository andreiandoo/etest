<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Aduce imaginea unei întrebări la noi pe disc.
 *
 * O întrebare care arată o intersecție nu poate fi rezolvată din text, deci
 * imaginea nu e un accesoriu: dacă nu o putem aduce, întrebarea nu are ce căuta
 * pe site. De aceea metoda aruncă în loc să întoarcă gol, iar conectorul
 * raportează rândul ca respins.
 *
 * Numele fișierului se face din adresa de proveniență, deci o a doua
 * sincronizare recunoaște ce a adus prima și nu descarcă de două ori aceeași
 * imagine. Nu se păstrează adresa în numele fișierului, doar amprenta ei.
 */
final class RemoteImageStore
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    /** Sub atât, ce s-a descărcat nu e o imagine, e o pagină de eroare. */
    private const MINIMUM_BYTES = 512;

    private const MAXIMUM_BYTES = 8388608;

    /**
     * @return array{path: string, bytes: int, fetched: bool}
     */
    public function fetch(string $url, string $directory): array
    {
        $disk = Storage::disk('public');
        $name = substr(sha1($url), 0, 16);

        foreach (self::TYPES as $extension) {
            $path = trim($directory, '/').'/'.$name.'.'.$extension;

            if ($disk->exists($path)) {
                return ['path' => $path, 'bytes' => (int) $disk->size($path), 'fetched' => false];
            }
        }

        $response = Http::timeout(60)
            ->withHeaders(['User-Agent' => 'e-test.ro content import (+https://e-test.ro)'])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Imaginea a răspuns cu codul '.$response->status().'.');
        }

        $body = $response->body();
        $bytes = strlen($body);

        if ($bytes < self::MINIMUM_BYTES) {
            throw new RuntimeException('Imaginea are doar '.$bytes.' octeți, deci nu e o imagine.');
        }

        if ($bytes > self::MAXIMUM_BYTES) {
            throw new RuntimeException('Imaginea are '.round($bytes / 1048576, 1).' MB, peste limita de 8 MB.');
        }

        $extension = self::TYPES[strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]))] ?? null;

        if ($extension === null) {
            throw new RuntimeException('Tipul „'.$response->header('Content-Type').'” nu e o imagine pe care o servim.');
        }

        $path = trim($directory, '/').'/'.$name.'.'.$extension;
        $disk->put($path, $body);

        return ['path' => $path, 'bytes' => $bytes, 'fetched' => true];
    }
}
