<?php

namespace App\Http\Resources;

use App\Models\Review;
use App\Support\StatusPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => StatusPresenter::present($this->status),
            'notes' => $this->notes,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'project_code' => $this->project->project_code,
                'project_name' => $this->project->project_name,
                'status' => StatusPresenter::present($this->project->status),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
