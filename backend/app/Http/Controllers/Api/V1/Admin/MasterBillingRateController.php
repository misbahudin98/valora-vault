<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMasterBillingRateRequest;
use App\Http\Requests\Admin\UpdateMasterBillingRateRequest;
use App\Models\BillingRateHistory;
use App\Models\MasterBillingRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MasterBillingRateController extends Controller
{
    /**
     * Protected core system rate keys that cannot be deleted.
     */
    protected const PROTECTED_KEYS = [
        'landing',
        'login',
        'register',
        'app_core',
        'menu_price',
        'database_table_price',
        'maintenance_menu_price',
    ];

    /**
     * Get all master billing rates and change histories.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', MasterBillingRate::class);

        $rates = MasterBillingRate::where('is_active', true)->orderBy('id', 'asc')->get();
        $histories = BillingRateHistory::orderBy('created_at', 'desc')->limit(100)->get();

        // Convert rates to key-value map for quick frontend consumption
        $ratesMap = [];
        foreach ($rates as $r) {
            $ratesMap[$r->rate_key] = (float) $r->price;
        }

        return response()->json([
            'data' => $rates,
            'rates_map' => $ratesMap,
            'histories' => $histories,
        ]);
    }

    /**
     * Store a new registered module or custom rate in Master Billing Rates.
     */
    public function store(StoreMasterBillingRateRequest $request): JsonResponse
    {
        Gate::authorize('create', MasterBillingRate::class);

        $validated = $request->validated();
        $rateKey = $validated['rate_key'] ?? null;
        if (! $rateKey) {
            $baseSlug = Str::slug($validated['name'], '_');
            $rateKey = 'modul_' . $baseSlug;
            $counter = 1;
            $originalKey = $rateKey;
            while (MasterBillingRate::where('rate_key', $rateKey)->exists()) {
                $rateKey = $originalKey . '_' . $counter;
                $counter++;
            }
        }

        $user = $request->user();
        $userName = $user ? $user->name : 'Admin Valora';

        $rate = DB::transaction(function () use ($validated, $rateKey, $userName) {
            $created = MasterBillingRate::create([
                'rate_key' => $rateKey,
                'name' => $validated['name'],
                'category' => $validated['category'] ?? 'registered_module',
                'price' => (float) $validated['price'],
                'unit' => $validated['unit'] ?? 'per menu modul',
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'has_create' => (bool) ($validated['has_create'] ?? true),
                'has_read' => (bool) ($validated['has_read'] ?? true),
                'has_update' => (bool) ($validated['has_update'] ?? true),
                'has_delete' => (bool) ($validated['has_delete'] ?? true),
                'has_grafik' => (bool) ($validated['has_grafik'] ?? false),
                'has_box_dashboard' => (bool) ($validated['has_box_dashboard'] ?? false),
                'has_import' => (bool) ($validated['has_import'] ?? false),
                'has_export' => (bool) ($validated['has_export'] ?? false),
                'has_filter' => (bool) ($validated['has_filter'] ?? true),
                'has_print' => (bool) ($validated['has_print'] ?? false),
                'has_notification' => (bool) ($validated['has_notification'] ?? false),
                'has_custom_logic' => (bool) ($validated['has_custom_logic'] ?? false),
            ]);

            BillingRateHistory::create([
                'rate_key' => $created->rate_key,
                'item_name' => $created->name,
                'old_price' => 0.0,
                'new_price' => (float) $created->price,
                'reason' => 'Pendaftaran katalog modul menu baru ke Master Tarif',
                'updated_by' => $userName,
            ]);

            return $created;
        });

        return response()->json([
            'message' => "Modul menu terdaftar '{$rate->name}' berhasil ditambahkan ke Master Tarif.",
            'data' => $rate,
        ], 201);
    }

    /**
     * Update a specific master billing rate and record an audit history trail.
     */
    public function update(UpdateMasterBillingRateRequest $request, string $rateKey): JsonResponse
    {
        Gate::authorize('update', MasterBillingRate::class);

        $rate = MasterBillingRate::where('rate_key', $rateKey)->first();

        if (! $rate) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Tarif Tidak Ditemukan',
                'status' => 404,
                'detail' => "Item tarif standar dengan kunci '{$rateKey}' tidak ditemukan.",
            ], 404);
        }

        $validated = $request->validated();
        $oldPrice = (float) $rate->price;
        $newPrice = (float) $validated['price'];
        $reason = (string) $validated['reason'];
        $user = $request->user();
        $userName = $user ? $user->name : 'Admin Valora';

        $capabilities = [
            'has_create', 'has_read', 'has_update', 'has_delete',
            'has_grafik', 'has_box_dashboard', 'has_import', 'has_export',
            'has_filter', 'has_print', 'has_notification', 'has_custom_logic',
        ];

        DB::transaction(function () use ($rate, $validated, $oldPrice, $newPrice, $reason, $userName, $capabilities) {
            $updateData = [
                'price' => $newPrice,
                'name' => $validated['name'] ?? $rate->name,
                'description' => $validated['description'] ?? $rate->description,
                'unit' => $validated['unit'] ?? $rate->unit,
                'category' => $validated['category'] ?? $rate->category,
            ];

            foreach ($capabilities as $cap) {
                if (array_key_exists($cap, $validated)) {
                    $updateData[$cap] = (bool) $validated[$cap];
                }
            }

            $rate->update($updateData);

            BillingRateHistory::create([
                'rate_key' => $rate->rate_key,
                'item_name' => $rate->name,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'reason' => $reason,
                'updated_by' => $userName,
            ]);
        });

        return response()->json([
            'message' => "Tarif standar untuk '{$rate->name}' berhasil diperbarui.",
            'data' => $rate->fresh(),
        ]);
    }

    /**
     * Delete a custom registered module rate. Core system rates are protected.
     */
    public function destroy(Request $request, string $rateKey): JsonResponse
    {
        Gate::authorize('delete', MasterBillingRate::class);

        if (in_array($rateKey, self::PROTECTED_KEYS, true)) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Tarif Inti Dilindungi',
                'status' => 422,
                'detail' => "Tarif standar sistem inti '{$rateKey}' tidak dapat dihapus karena merupakan pondasi arsitektur tagihan.",
            ], 422);
        }

        $rate = MasterBillingRate::where('rate_key', $rateKey)->first();

        if (! $rate) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Tarif Tidak Ditemukan',
                'status' => 404,
                'detail' => "Item tarif standar dengan kunci '{$rateKey}' tidak ditemukan.",
            ], 404);
        }

        $rateName = $rate->name;
        $oldPrice = (float) $rate->price;
        $user = $request->user();
        $userName = $user ? $user->name : 'Admin Valora';

        DB::transaction(function () use ($rate, $rateKey, $rateName, $oldPrice, $userName) {
            BillingRateHistory::create([
                'rate_key' => $rateKey,
                'item_name' => $rateName,
                'old_price' => $oldPrice,
                'new_price' => 0.0,
                'reason' => "Penghapusan katalog modul menu terdaftar: {$rateName}",
                'updated_by' => $userName,
            ]);

            $rate->delete();
        });

        return response()->json([
            'message' => "Katalog modul menu terdaftar '{$rateName}' berhasil dihapus dari Master Tarif.",
        ], 200);
    }
}
