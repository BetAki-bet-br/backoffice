<?php

namespace Database\Factories\Domain\Casino;

use App\Models\Domain\Casino\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlotFactory extends Factory
{
    protected $model = Slot::class;

    public function definition(): array
    {
        $provider = $this->faker->randomElement(['Novomatic', 'NetEnt', 'Playtech', 'Microgaming', 'Evolution']);
        
        return [
            'title' => $this->faker->unique()->words(3, true),
            'cover_url' => $this->faker->imageUrl(300, 400, 'games'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'provider' => $provider,
            'provider_game_id' => $this->faker->unique()->slug(),
            'tags' => $this->faker->randomElements(['novo', 'populares', 'jackpot', 'rtp-alto'], 2),
            'position' => $this->faker->numberBetween(0, 1000),
            'rtp' => $this->faker->randomFloat(2, 85, 98),
            'volatility' => $this->faker->randomElement(['low', 'medium', 'high']),
            'min_bet' => $this->faker->randomElement([0.01, 0.10, 0.25, 0.50, 1.00]),
            'max_bet' => $this->faker->randomElement([100.00, 250.00, 500.00, 1000.00, 2500.00]),
        ];
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

    public function highVolatility(): static
    {
        return $this->state(fn (array $attributes) => [
            'volatility' => 'high',
        ]);
    }

    public function mediumVolatility(): static
    {
        return $this->state(fn (array $attributes) => [
            'volatility' => 'medium',
        ]);
    }

    public function lowVolatility(): static
    {
        return $this->state(fn (array $attributes) => [
            'volatility' => 'low',
        ]);
    }
}
