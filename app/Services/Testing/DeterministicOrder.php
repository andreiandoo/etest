<?php

namespace App\Services\Testing;

final class DeterministicOrder
{
    /**
     * @param  array<int, int|string>  $values
     * @return array<int, int|string>
     */
    public function sort(array $values, int $seed, string $scope): array
    {
        usort($values, function (int|string $left, int|string $right) use ($seed, $scope): int {
            $leftHash = hash('sha256', $seed.'|'.$scope.'|'.$left);
            $rightHash = hash('sha256', $seed.'|'.$scope.'|'.$right);

            $comparison = strcmp($leftHash, $rightHash);

            return $comparison !== 0
                ? $comparison
                : strcmp((string) $left, (string) $right);
        });

        return $values;
    }
}
