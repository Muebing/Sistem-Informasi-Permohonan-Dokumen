<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $user->hasRole('applicant')
            ? $this->dashboardService->forApplicant($user)
            : $this->dashboardService->forReviewer();

        return response()->json(['dashboard' => $data]);
    }
}
