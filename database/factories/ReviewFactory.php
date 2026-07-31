<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'reviewer_id' => User::factory(),
            'status' => fake()->randomElement([
                ProjectStatus::Approved,
                ProjectStatus::Rejected,
                ProjectStatus::Revision,
            ]),
            'notes' => fake()->optional(0.9)->paragraph(),
        ];
    }
}
