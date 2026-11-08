<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telemetry\ReportClientErrorRequest;
use App\Services\SystemMonitoringService;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    /**
     * Ingest client-side JavaScript error reports with rate limiting.
     */
    public function reportError(ReportClientErrorRequest $request, SystemMonitoringService $monitoringService): JsonResponse
    {
        $result = $monitoringService->recordTelemetry(
            $request->validated(),
            $request->header('Referer'),
            $request->userAgent(),
            $request->ip(),
            $request->user()?->id
        );

        return response()->json($result, 200);
    }
}
