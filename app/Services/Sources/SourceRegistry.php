<?php

namespace App\Services\Sources;

use App\Services\Sources\Connectors\AncomRadioamatorConnector;
use App\Services\Sources\Contracts\SourceConnector;
use InvalidArgumentException;

/**
 * Lista conectorilor disponibili.
 *
 * Se adaugă câte unul pe măsură ce o sursă e lămurită și parsată. Lista e
 * scrisă de mână, nu descoperită automat: un conector care apare în produs
 * fără ca cineva să-l fi trecut aici e exact felul de import pe care nu-l
 * vrem.
 */
final class SourceRegistry
{
    /** @var array<int, class-string<SourceConnector>> */
    private const CONNECTORS = [
        AncomRadioamatorConnector::class,
    ];

    public function get(string $key): SourceConnector
    {
        foreach ($this->all() as $connector) {
            if ($connector->key() === $key) {
                return $connector;
            }
        }

        throw new InvalidArgumentException(
            'Nu există niciun conector „'.$key.'”. Disponibile: '.implode(', ', $this->keys()).'.'
        );
    }

    /**
     * @return array<int, SourceConnector>
     */
    public function all(): array
    {
        return array_map(static fn (string $class): SourceConnector => app($class), self::CONNECTORS);
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(static fn (SourceConnector $connector): string => $connector->key(), $this->all());
    }
}
