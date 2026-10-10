<?php

namespace Database\Factories\Tenancy;

use App\Tenancy\Models\Note;
use App\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
        ];
    }
}
