<?php

namespace App\Services\Sources\Connectors;

/**
 * Chestionarele pentru redobândirea permisului, din `docs/auto/DRPCIV_qa_R.csv`.
 *
 * Proba nu e a unei categorii de permis, ci a celui căruia i-a fost anulat
 * permisul, deci stă sub secțiunea ei și se numește ca atare — „redobândire”,
 * nu „categoria R”, care nu există.
 */
final class DrpcivRedobandireConnector extends DrpcivConnector
{
    protected function category(): string
    {
        return 'R';
    }

    protected function label(): string
    {
        return 'redobândirea permisului';
    }

    protected function taxonomySlug(): string
    {
        return 'redobandire-permis';
    }
}
