<?php

namespace App\Http\Resources;

use App\Models\ApprovalLog;
use App\Support\StatusPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApprovalLog */
class ApprovalLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'old_status' => StatusPresenter::present($this->old_status),
            'new_status' => StatusPresenter::present($this->new_status),
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
