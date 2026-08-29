<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Head Office',
            'address' => fake()->address(),
            'phone_primary' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_domestic' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
