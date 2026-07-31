<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'doc', 'docx']);

        return [
            'project_id' => Project::factory(),
            'original_name' => fake()->sentence(3).'.'.$ext,
            'stored_name' => fake()->uuid().'.'.$ext,
            'path' => 'documents/'.fake()->uuid().'.'.$ext,
            'mime_type' => match ($ext) {
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            },
            'size' => fake()->numberBetween(20_000, 4_900_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
