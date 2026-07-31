<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 30)->unique();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('applicant_id');
            $table->index('submitted_at');
            $table->index('created_at');
        });

        DB::statement(
            'ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK '
            ."(status IN ('draft', 'submitted', 'under_review', 'revision', 'approved', 'rejected'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
