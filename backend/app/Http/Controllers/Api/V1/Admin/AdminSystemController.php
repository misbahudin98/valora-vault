<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClearLogRequest;
use App\Http\Requests\Admin\FilterLogRequest;
use App\Http\Requests\Admin\ToggleMaintenanceRequest;
use App\Services\SystemMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSystemController extends Controller
{
    /**
     * Get system health, maintenance mode status, and log file metrics.
     */
    public function status(Request $request, SystemMonitoringService $systemService): JsonResponse
    {
        return response()->json($systemService->getSystemHealth(), 200);
    }

    /**
     * Toggle or update maintenance mode.
     */
    public function toggleMaintenance(ToggleMaintenanceRequest $request, SystemMonitoringService $systemService): JsonResponse
    {
        $isMaintenance = app()->isDownForMaintenance();
        $action = $request->getAction($isMaintenance);
        $secret = $request->getSecret();
        $retry = $request->getRetry();

        $result = $systemService->toggleMaintenance($action, $secret, $retry);

        return response()->json($result, 200);
    }

    /**
     * Retrieve and parse backend Laravel logs.
     */
    public function logs(FilterLogRequest $request, SystemMonitoringService $systemService): JsonResponse
    {
        $result = $systemService->getLaravelLogs(
            $request->getFilterLevel(),
            $request->getSearchQuery(),
            $request->getLimit(100)
        );

        return response()->json($result, 200);
    }

    /**
     * Retrieve and parse frontend client JS error logs.
     */
    public function clientLogs(FilterLogRequest $request, SystemMonitoringService $systemService): JsonResponse
    {
        $result = $systemService->getClientLogs(
            $request->getSearchQuery(),
            $request->getLimit(100)
        );

        return response()->json($result, 200);
    }

    /**
     * Clear log files.
     */
    public function clearLogs(ClearLogRequest $request, SystemMonitoringService $systemService): JsonResponse
    {
        $systemService->clearLogs($request->getLogType());

        return response()->json([
            'message' => 'Berkas log berhasil dibersihkan.',
        ], 200);
    }
}
