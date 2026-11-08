<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveBlueprintSpecRequest;
use App\Services\BlueprintStudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudioController extends Controller
{
    /**
     * Get root path for documentation.
     */
    protected function getDocPath(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'doc';
    }

    /**
     * List all feature specifications from doc/features/.
     */
    public function index(Request $request, BlueprintStudioService $studioService): JsonResponse
    {
        $featuresDir = $this->getDocPath() . DIRECTORY_SEPARATOR . 'features';
        $modules = $studioService->listModules($featuresDir);

        return response()->json([
            'data' => $modules,
            'doc_path' => 'doc/features/',
            'environment' => app()->environment(),
        ], 200);
    }

    /**
     * Generate & Save a structured SDD feature specification file.
     */
    public function saveSpec(SaveBlueprintSpecRequest $request, BlueprintStudioService $studioService): JsonResponse
    {
        $featuresDir = $this->getDocPath() . DIRECTORY_SEPARATOR . 'features';
        $result = $studioService->saveSpec($request->validated(), $featuresDir, $this->getDocPath());

        return response()->json($result, 201);
    }
}
