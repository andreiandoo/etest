<?php

namespace App\Services\Api;

final class ApiScopes
{
    public const CATALOG_READ = 'catalog:read';

    public const TESTS_READ = 'tests:read';

    public const QUESTIONS_READ = 'questions:read';

    public const ANSWERS_READ = 'answers:read';

    /** @var array<int, string> */
    public const ALL = [
        self::CATALOG_READ,
        self::TESTS_READ,
        self::QUESTIONS_READ,
        self::ANSWERS_READ,
    ];

    public static function isAllowed(string $scope): bool
    {
        return in_array($scope, self::ALL, true);
    }
}
