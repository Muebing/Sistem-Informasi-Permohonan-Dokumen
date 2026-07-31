<?php

namespace App\Support;

use App\Enums\ProjectStatus;

final class StatusPresenter
{
    /**
     * @return array{value: string, label: string, color: array{badge: string, dot: string}}|null
     */
    public static function present(?ProjectStatus $status): ?array
    {
        if ($status === null) {
            return null;
        }

        return [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ];
    }
}
