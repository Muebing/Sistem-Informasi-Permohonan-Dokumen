<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PROJECTS_PER_YEAR = 10000;

    /**
     * Seed demo data: applicants, reviewers and a large project backlog.
     */
    public function run(): void
    {
        $applicantRole = Role::findByName('applicant');
        $reviewerRole = Role::findByName('reviewer');

        $applicants = User::factory()->count(1000)->create();
        $applicantRole->users()->attach($applicants->pluck('id')->all());

        $reviewers = User::factory()->count(1000)->create();
        $reviewerRole->users()->attach($reviewers->pluck('id')->all());

        $applicantIds = $applicants->pluck('id')->all();
        $reviewerIds = $reviewers->pluck('id')->all();

        $this->seedProjects($applicantIds);
        $this->seedReviewsAndLogs($reviewerIds);
    }

    /**
     * @param  array<int, int>  $applicantIds
     */
    private function seedProjects(array $applicantIds): void
    {
        $statusCounts = [
            'draft' => 2500,
            'submitted' => 1500,
            'under_review' => 1000,
            'revision' => 1000,
            'approved' => 2500,
            'rejected' => 1500,
        ];

        $now = now()->format('Y-m-d H:i:s');
        $year = now()->year;
        $nonDraft = ['submitted', 'under_review', 'revision', 'approved', 'rejected'];

        foreach ($statusCounts as $status => $total) {
            for ($offset = 0; $offset < $total; $offset += 500) {
                $rows = [];

                foreach (range(1, min(500, $total - $offset)) as $ignored) {
                    $submittedAt = in_array($status, $nonDraft, true)
                        ? fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d H:i:s')
                        : null;

                    $rows[] = [
                        'project_code' => sprintf('SPRD-%d-%05d', $year, fake()->unique()->numberBetween(1, 100000)),
                        'project_name' => fake()->words(4, true),
                        'description' => fake()->optional(0.8)->paragraph(2),
                        'applicant_id' => $applicantIds[array_rand($applicantIds)],
                        'status' => $status,
                        'submitted_at' => $submittedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('projects')->insert($rows);
            }
        }
    }

    /**
     * @param  array<int, int>  $reviewerIds
     */
    private function seedReviewsAndLogs(array $reviewerIds): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $projectCount = self::PROJECTS_PER_YEAR;

        Project::query()
            ->select('id', 'status', 'applicant_id', 'submitted_at')
            ->chunkById(500, function ($projects) use ($reviewerIds, $now): void {
                $activityRows = [];
                $submitLogRows = [];
                $reviewRows = [];
                $decisionLogRows = [];
                $startLogRows = [];

                foreach ($projects as $project) {
                    $status = $project->status->value;

                    $createdAt = $project->submitted_at?->format('Y-m-d H:i:s') ?? $now;

                    $activityRows[] = [
                        'user_id' => $project->applicant_id,
                        'project_id' => $project->id,
                        'activity' => 'created project',
                        'ip_address' => null,
                        'user_agent' => null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    if ($status === 'draft') {
                        continue;
                    }

                    $submitLogRows[] = [
                        'project_id' => $project->id,
                        'user_id' => $project->applicant_id,
                        'action' => 'submit',
                        'old_status' => 'draft',
                        'new_status' => 'submitted',
                        'notes' => 'Project submitted for review.',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    if ($status === 'under_review') {
                        $startLogRows[] = [
                            'project_id' => $project->id,
                            'user_id' => $reviewerIds[array_rand($reviewerIds)],
                            'action' => 'review',
                            'old_status' => 'submitted',
                            'new_status' => 'under_review',
                            'notes' => 'Review started.',
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ];

                        continue;
                    }

                    if (! in_array($status, ['approved', 'rejected', 'revision'], true)) {
                        continue;
                    }

                    $reviewerId = $reviewerIds[array_rand($reviewerIds)];
                    $action = match ($status) {
                        'approved' => 'approve',
                        'rejected' => 'reject',
                        default => 'request_revision',
                    };

                    $reviewRows[] = [
                        'project_id' => $project->id,
                        'reviewer_id' => $reviewerId,
                        'status' => $status,
                        'notes' => fake()->optional(0.6)->sentence(12),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];

                    $decisionLogRows[] = [
                        'project_id' => $project->id,
                        'user_id' => $reviewerId,
                        'action' => $action,
                        'old_status' => 'under_review',
                        'new_status' => $status,
                        'notes' => fake()->optional(0.4)->sentence(10),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }

                $this->insertBatches($activityRows, 'activity_logs');
                $this->insertBatches($submitLogRows, 'approval_logs');
                $this->insertBatches($reviewRows, 'reviews');
                $this->insertBatches($decisionLogRows, 'approval_logs');
                $this->insertBatches($startLogRows, 'approval_logs');
            });

        $this->command->info("Seeded {$projectCount} projects, 1000 applicants, 1000 reviewers.");
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertBatches(array $rows, string $table): void
    {
        foreach (array_chunk($rows, 500) as $batch) {
            DB::table($table)->insert($batch);
        }
    }
}
