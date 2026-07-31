<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index('reviewer_id');
            $table->index('status');
        });

        DB::statement(
            'ALTER TABLE reviews ADD CONSTRAINT reviews_status_check CHECK '
            ."(status IN ('approved', 'rejected', 'revision'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
