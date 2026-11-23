<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'name' => 'Test User',
            'email' => Str::uuid().'@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'agent',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
