<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    /**
     * Lightweight option lists for frontend filters.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();

        $data = match ($type) {
            'applicants' => User::role('applicant')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'reviewers' => User::role('reviewer')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            default => [],
        };

        return response()->json(['data' => $data]);
    }
}
