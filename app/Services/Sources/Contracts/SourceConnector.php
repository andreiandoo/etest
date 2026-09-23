<?php

namespace App\Services\Sources\Contracts;

use App\Models\Source;

/**
 * Un conector pe autoritate, nu un scraper universal.
 *
 * Fiecare instituție publică altfel: PDF cu răspunsul marcat, PDF cu barem
 * separat, DOC cu răspunsul îngroșat. Un singur parser care le acoperă pe
 * toate ar fi un parser care nu e corect pe niciuna, așa că fiecare sursă își
 * aduce regulile ei în spatele aceleiași interfețe.
 *
 * Citirea propriu-zisă e în `SingleDocumentSource` sau `MultiDocumentSource`,
 * după cum sursa e un fișier sau un set de fișiere.
 */
interface SourceConnector
{
    /**
     * Cheia din linia de comandă: `php artisan sources:sync <cheie>`.
     */
    public function key(): string;

    /**
     * Rândul din registru, așa cum trebuie să arate după sincronizare.
     *
     * @return array<string, mixed>
     */
    public function definition(): array;

    /**
     * Pregătește structura de care are nevoie importul — de obicei capitolele
     * din taxonomie, ca întrebările să aibă unde ateriza.
     */
    public function prepare(Source $source): void;

    /**
     * Transformă întrebările citite în rânduri pentru importatorul de conținut.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array;

    /**
     * Construiește testele de exercițiu din întrebările tocmai importate.
     *
     * Un import care lasă în urmă doar întrebări nu produce nicio pagină pe
     * care cineva să exerseze, iar catalogul public listează teste.
     *
     * @return int numărul de teste create sau actualizate
     */
    public function buildTests(Source $source): int;
}
