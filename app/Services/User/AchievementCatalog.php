<?php

namespace App\Services\User;

final class AchievementCatalog
{
    /**
     * @return array<string, array{title:string,description:string}>
     */
    public function all(): array
    {
        return [
            'first_test' => [
                'title' => 'Primul pas',
                'description' => 'Ai finalizat primul test.',
            ],
            'five_tests' => [
                'title' => 'În ritm',
                'description' => 'Ai finalizat 5 teste.',
            ],
            'twenty_tests' => [
                'title' => 'Consecvență',
                'description' => 'Ai finalizat 20 de teste.',
            ],
            'perfect_score' => [
                'title' => 'Scor perfect',
                'description' => 'Ai obținut 100% la un test.',
            ],
            'streak_3' => [
                'title' => '3 zile la rând',
                'description' => 'Ai învățat în 3 zile consecutive.',
            ],
            'streak_7' => [
                'title' => 'O săptămână',
                'description' => 'Ai învățat în 7 zile consecutive.',
            ],
            'xp_100' => [
                'title' => '100 XP',
                'description' => 'Ai acumulat primele 100 XP.',
            ],
            'explorer_3' => [
                'title' => 'Explorator',
                'description' => 'Ai finalizat teste din 3 domenii diferite.',
            ],
        ];
    }

    /**
     * @return array{title:string,description:string}
     */
    public function get(string $key): array
    {
        return $this->all()[$key] ?? [
            'title' => $key,
            'description' => '',
        ];
    }
}
