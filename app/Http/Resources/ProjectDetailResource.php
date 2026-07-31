<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Support\StatusPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'project_name' => $this->project_name,
            'description' => $this->description,
            'status' => StatusPresenter::present($this->status),
            'applicant' => $this->whenLoaded('applicant', fn () => [
                'id' => $this->applicant->id,
                'name' => $this->applicant->name,
                'email' => $this->applicant->email,
            ]),
            'documents' => ProjectDocumentResource::collection($this->whenLoaded('documents')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
