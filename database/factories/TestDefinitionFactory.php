<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Enums\TestMode;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TestDefinition> */
class TestDefinitionFactory extends Factory
{
    protected $model = TestDefinition::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'vertical_id' => Vertical::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 9999),
            'description' => fake()->paragraph(),
            'mode' => TestMode::Practice,
            'status' => PublicationStatus::Published,
            'randomize_questions' => false,
            'randomize_options' => false,
            'allow_review' => true,
            'show_explanations' => true,
            'published_at' => now(),
        ];
    }
}
