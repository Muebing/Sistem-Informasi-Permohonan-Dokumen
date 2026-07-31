<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_code' => fn () => $this->generateCode(),
            'project_name' => fake()->words(4, true),
            'description' => fake()->optional(0.8)->paragraph(2),
            'applicant_id' => User::factory(),
            'status' => ProjectStatus::Draft,
            'submitted_at' => null,
        ];
    }

    /**
     * Assign a concrete status so related timestamps stay consistent.
     */
    public function withStatus(ProjectStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'submitted_at' => in_array($status, [ProjectStatus::Submitted, ProjectStatus::UnderReview, ProjectStatus::Revision, ProjectStatus::Approved, ProjectStatus::Rejected], true)
                ? fake()->dateTimeBetween('-12 months', 'now')
                : null,
        ]);
    }

    private function generateCode(): string
    {
        return 'SPRD-'.now()->year.'-'.strtoupper(fake()->unique()->bothify('#####'));
    }
}
