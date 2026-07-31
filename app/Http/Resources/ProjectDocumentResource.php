<?php

namespace App\Http\Resources;

use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectDocument */
class ProjectDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'stored_name' => $this->stored_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_human' => $this->size ? number_format($this->size / 1024, 1).' KB' : '0 KB',
            'hash' => $this->hash,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'download_url' => route('projects.documents.download', [
                'project' => $this->project_id,
                'document' => $this->id,
            ]),
        ];
    }
}
