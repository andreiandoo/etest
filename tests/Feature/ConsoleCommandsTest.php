<?php

use Illuminate\Support\Facades\Artisan;

/**
 * Comenzile proiectului sunt descoperite automat din `app/Console/Commands`.
 * Descoperirea tace când ceva nu e în regulă — un nume de clasă care nu se
 * potrivește cu fișierul, o semnătură scrisă greșit — iar comanda pur și simplu
 * nu există, fără nicio eroare. Aici se vede.
 */
test('every command the project offers is registered', function () {
    $registered = array_keys(Artisan::all());

    foreach ([
        'content:activate',
        'content:purge-demo',
        'content:status',
        'sources:sync',
    ] as $command) {
        expect($registered)->toContain($command);
    }
});

test('every class in the commands folder ends up registered', function () {
    $registered = array_keys(Artisan::all());
    $files = glob(app_path('Console/Commands/*.php')) ?: [];

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $class = 'App\\Console\\Commands\\'.basename($file, '.php');
        $signature = (string) ((new ReflectionClass($class))->getDefaultProperties()['signature'] ?? '');

        expect(preg_match('/^\s*([\w:.-]+)/', $signature, $found))->toBe(1, $class);
        expect($registered)->toContain($found[1]);
    }
});
