<?php

namespace App\Jobs;

use App\Models\ProjectDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public readonly ProjectDocument $document,
    ) {}

    /**
     * Compute the file checksum and mark the document as processed.
     */
    public function handle(): void
    {
        $path = $this->document->path;

        if (! Storage::disk('documents')->exists($path)) {
            Log::warning('Document file missing during processing', [
                'document_id' => $this->document->id,
                'path' => $path,
            ]);

            return;
        }

        $hash = hash_file('sha256', Storage::disk('documents')->path($path));

        $this->document->forceFill([
            'hash' => $hash ?: null,
            'processed_at' => now(),
        ])->save();
    }
}
