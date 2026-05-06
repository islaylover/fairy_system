<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UsageController extends Controller
{
    public function __construct(
        private readonly UsageService $usageService
    ) {}

    public function monthly(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $yearMonth = $request->query('year_month');

        return response()->json(
            $this->usageService->getMonthlySummaryBoth($userId, is_string($yearMonth) ? $yearMonth : null)
        );
    }
}
