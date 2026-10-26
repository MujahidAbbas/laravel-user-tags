<?php

namespace MujahidAbbas\LaravelUserTags\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use MujahidAbbas\LaravelUserTags\Traits\OwnsUserTags;

class User extends Authenticatable
{
    use HasFactory;
    use OwnsUserTags;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<static>
     */
    protected static function newFactory()
    {
        return new class extends Factory
        {
            protected $model = User::class;

            public function definition(): array
            {
                return [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                ];
            }
        };
    }
}
