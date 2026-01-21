<?php

namespace Database\Factories\Domain\Casino;

use App\Models\Domain\Casino\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'external_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'game_count' => $this->faker->numberBetween(10, 200),
            'status' => 'active',
            'verticals' => ['slots'],
        ];
    }
}
