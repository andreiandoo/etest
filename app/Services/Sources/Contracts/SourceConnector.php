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
     * @return array{total: int, questions: array<int, array<string, mixed>>, rejected: array<int, array<string, string>>}
     */
    public function parse(string $text): array;

    /**
     * Transformă întrebările citite în rânduri pentru importatorul de conținut.
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    public function rows(Source $source, array $questions): array;
}
