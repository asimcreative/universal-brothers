<?php

namespace Database\Factories;

use App\Models\PackageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Package';

        return [
            'package_category_id' => PackageCategory::factory(),
            'code' => strtoupper(fake()->unique()->bothify('UB###')),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'summary' => fake()->sentence(),
            'duration_days' => fake()->numberBetween(5, 20),
            'duration_label' => fake()->numberBetween(5, 20).' Days Package',
            'currency' => 'USD',
            'starting_price' => fake()->numberBetween(1000, 20000),
            'is_featured' => false,
            'is_seasonal' => false,
            'status' => 'published',
            'published_at' => now(),
            'sort_order' => 0,
        ];
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }
}
