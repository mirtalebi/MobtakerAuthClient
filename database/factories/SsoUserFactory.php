<?php

namespace MobtakerSystem\SsoClient\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MobtakerSystem\SsoClient\Models\SsoUser;

class SsoUserFactory extends Factory
{
    protected $model = SsoUser::class;

    public function definition(): array
    {
        return [
            'sso_id' => fake()->unique()->uuid(),
            'sso_data' => [
                'id' => fake()->unique()->numerify('###'),
                'mobile' => fake()->unique()->safemobile(),
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'avatar' => fake()->imageUrl(),
            ],
            'token' => fake()->sha256(),
            'synced_at' => now(),
        ];
    }

    public function forUser($user): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id ?? null,
        ]);
    }
}
