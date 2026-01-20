<?php

namespace Database\Factories\Domain\Casino;

use App\Models\Domain\Casino\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'verticals' => $this->faker->randomElements(['slots', 'live'], 1),
            'type' => $this->faker->randomElement([
                'game-list',
                'recent-games',
                'mais-premiados',
                'winners-list',
                'top-10-list',
                'providers-carousel'
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'position' => $this->faker->numberBetween(0, 1000),
            'meta' => [],
        ];
    }

    public function slots(): static
    {
        return $this->state(fn (array $attributes) => [
            'verticals' => ['slots'],
        ]);
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'verticals' => ['live'],
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
