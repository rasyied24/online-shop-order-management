<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(GetDashboardSummaryAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute());
    }
}
