<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAgencyProfileRequest;
use App\Models\AgencyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AgencyProfileController extends Controller
{
    /**
     * Get the active Agency Profile & Valora Identity.
     */
    public function show(): JsonResponse
    {
        $profile = AgencyProfile::getProfile();

        return response()->json([
            'agency' => $profile,
            'data' => $profile,
        ]);
    }

    /**
     * Update the Agency Profile & Valora Identity.
     */
    public function update(UpdateAgencyProfileRequest $request): JsonResponse
    {
        Gate::authorize('update', AgencyProfile::class);

        $validated = $request->validated();
        $profile = AgencyProfile::getProfile();

        DB::transaction(function () use ($profile, $validated) {
            $profile->update($validated);
        });

        $fresh = $profile->fresh();

        return response()->json([
            'message' => 'Profil legal & identitas agency Valora berhasil diperbarui.',
            'agency' => $fresh,
            'data' => $fresh,
        ]);
    }
}
