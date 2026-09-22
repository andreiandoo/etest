<?php

use Illuminate\Support\Facades\Blade;

/**
 * Fiecare view Blade trebuie să compileze în PHP valid.
 *
 * Blade extrage blocurile `@php ... @endphp` cu o expresie regulată non-greedy
 * care pornește de la primul `@php` din fișier. Dacă un `@php(...)` inline
 * apare înaintea unui bloc, potrivirea merge de la forma inline până la
 * `@endphp`-ul blocului și înghite tot ce e între ele — zeci de linii de
 * markup ajung într-un bloc PHP nevalid, iar pagina dă 500 abia în browser.
 *
 * Verificarea de aici e ieftină și prinde întreaga clasă de probleme, nu doar
 * cazul acela: orice directivă nedeschisă sau neînchisă iese la iveală.
 */
test('every blade view compiles to valid php', function () {
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }

    expect($files)->not->toBeEmpty();

    $broken = [];

    foreach ($files as $path) {
        $compiled = Blade::compileString((string) file_get_contents($path));

        try {
            // TOKEN_PARSE aruncă ParseError pentru PHP invalid, fără să execute nimic.
            token_get_all($compiled, TOKEN_PARSE);
        } catch (ParseError $error) {
            $broken[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $path)
                .' — '.$error->getMessage();
        }
    }

    expect($broken)->toBe([]);
});
