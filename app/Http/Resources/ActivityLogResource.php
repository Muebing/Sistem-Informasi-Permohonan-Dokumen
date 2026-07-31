<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity' => $this->activity,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project?->id,
                'project_code' => $this->project?->project_code,
                'project_name' => $this->project?->project_name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
