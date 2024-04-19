<?php

namespace Database\Factories;

use App\Models\PasswordGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PasswordGeneratorFactory extends Factory
{
    protected $model = PasswordGenerator::class;

    public function definition(): array
    {
        return [
            'pass_hash' => password_hash(str_repeat(mt_rand(0, 9), 32), PASSWORD_DEFAULT),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
