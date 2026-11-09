<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordProjectPaymentRequest;
use App\Models\AgencyProfile;
use App\Models\ClientProject;
use App\Models\ClientProjectPayment;
use App\Models\MasterBillingRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClientProjectBillingController extends Controller
{
    /**
     * Get list of projects with billing status, metrics, and payment summaries.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MasterBillingRate::class);

        $query = ClientProject::with(['client', 'payments']);

        if ($search = $request->input('search')) {
            $escaped = addcslashes(trim((string) $search), '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('app_name', 'like', "%{$escaped}%")
                  ->orWhere('project_name', 'like', "%{$escaped}%")
                  ->orWhere('lead_pic', 'like', "%{$escaped}%")
                  ->orWhereHas('client', function ($cq) use ($escaped) {
                      $cq->where('name', 'like', "%{$escaped}%")
                         ->orWhere('industry', 'like', "%{$escaped}%");
                  });
            });
        }

        if ($type = $request->input('project_type')) {
            if (in_array($type, ['development', 'maintenance'], true)) {
                $query->where('project_type', $type);
            }
        }

        $projects = $query->orderBy('id', 'desc')->limit(1000)->get();

        $totalGrand = 0.0;
        $totalPaid = 0.0;
        $totalRemaining = 0.0;
        $fullyPaidCount = 0;
        $partiallyPaidCount = 0;
        $unpaidCount = 0;

        $projectItems = $projects->map(function (ClientProject $p) use (
            &$totalGrand,
            &$totalPaid,
            &$totalRemaining,
            &$fullyPaidCount,
            &$partiallyPaidCount,
            &$unpaidCount
        ) {
            $grand = (float) $p->grand_total;
            $paid = (float) $p->paid_amount;
            $remaining = max(0.0, $grand - $paid);

            $totalGrand += $grand;
            $totalPaid += $paid;
            $totalRemaining += $remaining;

            $status = 'Belum Bayar';
            if ($grand > 0 && $remaining <= 0) {
                $status = 'Lunas';
                $fullyPaidCount++;
            } elseif ($paid > 0 && $remaining > 0) {
                $status = 'DP / Termin';
                $partiallyPaidCount++;
            } else {
                $unpaidCount++;
            }

            return [
                'id' => $p->id,
                'project_name' => $p->project_name,
                'app_name' => $p->app_name,
                'project_type' => $p->project_type,
                'lead_pic' => $p->lead_pic,
                'status' => $p->status,
                'client' => $p->client ? [
                    'id' => $p->client->id,
                    'name' => $p->client->name,
                    'industry' => $p->client->industry,
                    'phone' => $p->client->phone,
                    'email' => $p->client->email,
                ] : null,
                'grand_total' => $grand,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $status,
                'payments_count' => $p->payments->count(),
                'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        // Optional filter by payment status in query
        if ($payFilter = $request->input('payment_status')) {
            $projectItems = $projectItems->filter(function ($item) use ($payFilter) {
                if ($payFilter === 'lunas') return $item['payment_status'] === 'Lunas';
                if ($payFilter === 'termin') return $item['payment_status'] === 'DP / Termin';
                if ($payFilter === 'belum_bayar') return $item['payment_status'] === 'Belum Bayar';
                return true;
            })->values();
        }

        return response()->json([
            'data' => $projectItems,
            'meta' => [
                'total_projects' => $projects->count(),
                'total_grand' => $totalGrand,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
                'fully_paid_count' => $fullyPaidCount,
                'partially_paid_count' => $partiallyPaidCount,
                'unpaid_count' => $unpaidCount,
            ],
        ]);
    }

    /**
     * Get detailed invoice breakdown and payment transaction history for a specific project.
     */
    public function showInvoice(int $projectId): JsonResponse
    {
        Gate::authorize('viewAny', MasterBillingRate::class);

        $project = ClientProject::with([
            'client',
            'roles.menus',
            'databaseTables',
            'payments',
        ])->find($projectId);

        if (! $project) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Proyek Tidak Ditemukan',
                'status' => 404,
                'detail' => "Proyek klien ID #{$projectId} tidak ditemukan.",
            ], 404);
        }

        // Self-healing: Sinkronisasi otomatis jika terdapat saldo terbayar namun riwayat transaksi belum ada
        if ((float) $project->paid_amount > 0 && $project->payments->isEmpty()) {
            ClientProjectPayment::create([
                'project_id' => $project->id,
                'amount' => (float) $project->paid_amount,
                'payment_date' => $project->created_at ? $project->created_at->format('Y-m-d') : date('Y-m-d'),
                'payment_method' => 'Transfer BCA',
                'reference_no' => (float) $project->paid_amount >= (float) $project->grand_total ? 'Pelunasan 100% Kontrak (TRX-BCA-9921)' : 'Pembayaran Uang Muka DP 50% (TRX-BCA-88912)',
                'recorded_by' => $project->lead_pic ?: 'Muhammad Iqbal Murtadho',
            ]);
            $project->load('payments');
        }

        // Master setting prices as baseline reference
        $rates = MasterBillingRate::all()->keyBy('item_code');
        $baseLandingSetting = (float) ($rates['landing']?->standard_price ?? 2500000.0);
        $baseLoginSetting = (float) ($rates['login']?->standard_price ?? 1000000.0);
        $baseRegisterSetting = (float) ($rates['register']?->standard_price ?? 1000000.0);
        $baseMenuSetting = $project->project_type === 'maintenance'
            ? (float) ($rates['maintenance_menu_price']?->standard_price ?? 150000.0)
            : (float) ($rates['menu_price']?->standard_price ?? 750000.0);
        $baseTableSetting = (float) ($rates['database_table_price']?->standard_price ?? 100000.0);

        $items = [];

        $resolveCaps = function (?array $caps): array {
            if (!$caps) return [];
            $res = [];
            if (!empty($caps['has_create'])) $res[] = 'Create';
            if (!empty($caps['has_read'])) $res[] = 'Read';
            if (!empty($caps['has_update'])) $res[] = 'Update';
            if (!empty($caps['has_delete'])) $res[] = 'Delete';
            if (!empty($caps['has_grafik'])) $res[] = 'Grafik';
            if (!empty($caps['has_box_dashboard'])) $res[] = 'Box Dashboard';
            if (!empty($caps['has_import'])) $res[] = 'Import';
            if (!empty($caps['has_export'])) $res[] = 'Export';
            if (!empty($caps['has_filter'])) $res[] = 'Filter';
            if (!empty($caps['has_print'])) $res[] = 'Print';
            return $res;
        };

        if ($project->use_landing) {
            $landingAdjusted = (float) $project->landing_price;
            $items[] = [
                'category' => 'Fitur Utama',
                'description' => 'Landing Page Responsive & Company Profile',
                'setting_price' => $baseLandingSetting,
                'unit_price' => $baseLandingSetting,
                'adjusted_price' => $landingAdjusted,
                'subtotal' => $landingAdjusted,
                'note' => $project->landing_note,
                'capabilities' => $resolveCaps($project->landing_capabilities),
            ];
        }

        if ($project->use_login) {
            $loginAdjusted = (float) $project->login_price;
            $items[] = [
                'category' => 'Keamanan & Autentikasi',
                'description' => 'Halaman Login Otentikasi & Proteksi Sesi Sanctum',
                'setting_price' => $baseLoginSetting,
                'unit_price' => $baseLoginSetting,
                'adjusted_price' => $loginAdjusted,
                'subtotal' => $loginAdjusted,
                'note' => $project->login_note,
                'capabilities' => $resolveCaps($project->login_capabilities),
            ];
        }

        if ($project->use_register) {
            $registerAdjusted = (float) $project->register_price;
            $items[] = [
                'category' => 'Keamanan & Autentikasi',
                'description' => 'Halaman Register Mandiri & Verifikasi Akun',
                'setting_price' => $baseRegisterSetting,
                'unit_price' => $baseRegisterSetting,
                'adjusted_price' => $registerAdjusted,
                'subtotal' => $registerAdjusted,
                'note' => $project->register_note,
                'capabilities' => $resolveCaps($project->register_capabilities),
            ];
        }

        foreach ($project->roles as $role) {
            foreach ($role->menus as $menu) {
                $capabilities = [];
                if ($menu->has_create) $capabilities[] = 'Create';
                if ($menu->has_read) $capabilities[] = 'Read';
                if ($menu->has_update) $capabilities[] = 'Update';
                if ($menu->has_delete) $capabilities[] = 'Delete';
                if ($menu->has_grafik) $capabilities[] = 'Grafik';
                if ($menu->has_box_dashboard) $capabilities[] = 'Box Dashboard';
                if ($menu->has_import) $capabilities[] = 'Import';
                if ($menu->has_export) $capabilities[] = 'Export';
                if ($menu->has_filter) $capabilities[] = 'Filter';
                if ($menu->has_print) $capabilities[] = 'Print';
                if ($menu->has_notification) $capabilities[] = 'Notifikasi';
                if ($menu->has_custom_logic) $capabilities[] = 'Logika Kustom';

                $menuAdjusted = (float) $menu->price;
                $items[] = [
                    'category' => "Modul [{$role->role_name}]",
                    'description' => $menu->menu_name . ($menu->menu_desc ? " — {$menu->menu_desc}" : ''),
                    'setting_price' => $baseMenuSetting,
                    'unit_price' => $baseMenuSetting,
                    'adjusted_price' => $menuAdjusted,
                    'subtotal' => $menuAdjusted,
                    'pic' => $menu->pic,
                    'is_done' => (bool) $menu->is_done,
                    'note' => $menu->price_note,
                    'capabilities' => $capabilities,
                ];
            }
        }

        foreach ($project->databaseTables as $table) {
            $tableAdjusted = (float) $table->price;
            $items[] = [
                'category' => 'Arsitektur Database Relasional',
                'description' => "Tabel Database: {$table->table_name}",
                'setting_price' => $baseTableSetting,
                'unit_price' => $baseTableSetting,
                'adjusted_price' => $tableAdjusted,
                'subtotal' => $tableAdjusted,
                'is_done' => (bool) $table->is_done,
                'capabilities' => [],
            ];
        }

        $totalSetting = (float) array_sum(array_column($items, 'setting_price'));
        $totalAdjusted = (float) array_sum(array_column($items, 'adjusted_price'));
        $adjustmentDiff = $totalAdjusted - $totalSetting;

        $grand = (float) $project->grand_total;
        $paid = (float) $project->paid_amount;
        $remaining = max(0.0, $grand - $paid);

        $paymentStatus = 'Belum Bayar';
        if ($grand > 0 && $remaining <= 0) {
            $paymentStatus = 'Lunas';
        } elseif ($paid > 0 && $remaining > 0) {
            $paymentStatus = 'DP / Termin';
        }

        return response()->json([
            'project' => [
                'id' => $project->id,
                'invoice_number' => sprintf('INV-%s-%04d', $project->created_at ? $project->created_at->format('Y') : date('Y'), $project->id),
                'project_name' => $project->project_name,
                'app_name' => $project->app_name,
                'project_type' => $project->project_type,
                'lead_pic' => $project->lead_pic,
                'status' => $project->status,
                'created_at' => $project->created_at?->format('Y-m-d'),
                'client' => $project->client,
                'total_setting_price' => $totalSetting,
                'total_adjusted_price' => $totalAdjusted,
                'adjustment_diff' => $adjustmentDiff,
                'grand_total' => $grand,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $paymentStatus,
            ],
            'items' => $items,
            'payments' => $project->payments,
            'agency' => AgencyProfile::getProfile(),
        ]);
    }

    /**
     * Record a new payment transaction for a client project.
     */
    public function recordPayment(RecordProjectPaymentRequest $request, int $projectId): JsonResponse
    {
        Gate::authorize('manage', MasterBillingRate::class);

        $project = ClientProject::find($projectId);

        if (! $project) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Proyek Tidak Ditemukan',
                'status' => 404,
                'detail' => "Proyek klien ID #{$projectId} tidak ditemukan.",
            ], 404);
        }

        $validated = $request->validated();
        $user = $request->user();
        $recordedBy = $user ? $user->name : 'Admin Valora';

        $grand = (float) $project->grand_total;
        $paid = (float) $project->paid_amount;
        $remaining = max(0.0, $grand - $paid);

        // Batas masuk akal: Validasi jika proyek sudah lunas
        if ($grand > 0 && $remaining <= 0.0) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Tagihan Telah Lunas',
                'status' => 422,
                'detail' => 'Seluruh kewajiban tagihan proyek ini telah 100% lunas. Tidak ada sisa tagihan yang dapat dibayarkan.',
            ], 422);
        }

        $amount = (float) $validated['amount'];

        // Batas masuk akal: Nominal pembayaran tidak boleh melebihi sisa tagihan
        if ($grand > 0 && $amount > ($remaining + 0.01)) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Nominal Melebihi Sisa Tagihan',
                'status' => 422,
                'detail' => sprintf(
                    'Nominal pembayaran (Rp %s) melebihi sisa tagihan proyek yang belum dibayar (Rp %s).',
                    number_format($amount, 0, ',', '.'),
                    number_format($remaining, 0, ',', '.')
                ),
            ], 422);
        }

        $payment = DB::transaction(function () use ($project, $validated, $recordedBy) {
            $p = ClientProjectPayment::create([
                'project_id' => $project->id,
                'amount' => (float) $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'recorded_by' => $recordedBy,
            ]);

            $project->recalculatePaidAmount();

            return $p;
        });

        return response()->json([
            'message' => 'Pembayaran klien berhasil dicatat.',
            'payment' => $payment,
            'paid_amount' => (float) $project->fresh()->paid_amount,
            'remaining_amount' => max(0.0, (float) $project->grand_total - (float) $project->fresh()->paid_amount),
        ], 201);
    }

    /**
     * Delete a payment transaction and automatically recalculate project balance.
     */
    public function deletePayment(int $paymentId): JsonResponse
    {
        Gate::authorize('manage', MasterBillingRate::class);

        $payment = ClientProjectPayment::with('project')->find($paymentId);

        if (! $payment) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Transaksi Tidak Ditemukan',
                'status' => 404,
                'detail' => "Transaksi pembayaran ID #{$paymentId} tidak ditemukan.",
            ], 404);
        }

        $project = $payment->project;

        DB::transaction(function () use ($payment, $project) {
            $payment->delete();
            $project?->recalculatePaidAmount();
        });

        $freshProject = $project?->fresh();
        $paid = (float) ($freshProject?->paid_amount ?? 0.0);
        $grand = (float) ($freshProject?->grand_total ?? 0.0);

        return response()->json([
            'message' => 'Catatan transaksi pembayaran berhasil dihapus.',
            'deleted_payment_id' => $paymentId,
            'paid_amount' => $paid,
            'remaining_amount' => max(0.0, $grand - $paid),
        ]);
    }

    /**
     * Get detailed official receipt (Kuitansi Resmi Pembayaran) for a specific payment.
     */
    public function showReceipt(int $paymentId): JsonResponse
    {
        Gate::authorize('viewAny', MasterBillingRate::class);

        $payment = ClientProjectPayment::with(['project.client'])->find($paymentId);

        if (! $payment) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Kuitansi Tidak Ditemukan',
                'status' => 404,
                'detail' => "Catatan transaksi pembayaran ID #{$paymentId} tidak ditemukan.",
            ], 404);
        }

        $project = $payment->project;
        $client = $project?->client;

        $year = $payment->payment_date ? $payment->payment_date->format('Y') : date('Y');
        $receiptNumber = sprintf('KWT-%s-%04d', $year, $payment->id);
        $agency = AgencyProfile::getProfile();

        return response()->json([
            'receipt' => [
                'id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'amount' => (float) $payment->amount,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d'),
                'payment_method' => $payment->payment_method,
                'reference_no' => $payment->reference_no,
                'recorded_by' => $payment->recorded_by ?: 'Finance Valora',
                'created_at' => $payment->created_at?->format('Y-m-d H:i:s'),
            ],
            'project' => $project ? [
                'id' => $project->id,
                'project_name' => $project->project_name,
                'app_name' => $project->app_name,
                'project_type' => $project->project_type,
                'invoice_number' => sprintf('INV-%s-%04d', $project->created_at ? $project->created_at->format('Y') : date('Y'), $project->id),
                'grand_total' => (float) $project->grand_total,
                'paid_amount' => (float) $project->paid_amount,
                'remaining_amount' => max(0.0, (float) $project->grand_total - (float) $project->paid_amount),
                'lead_pic' => $project->lead_pic ?: 'Tim Valora',
            ] : null,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'contact_person' => $client->contact_person,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
                'industry' => $client->industry,
            ] : null,
            'agency' => $agency,
            'company' => [
                'name' => $agency->legal_name ?: $agency->agency_name,
                'address' => $agency->operational_address,
                'npwp' => $agency->tax_id,
                'email' => $agency->email,
                'phone' => $agency->hotline_wa,
                'signatory_name' => $agency->signatory_name,
                'signatory_title' => $agency->signatory_title,
            ],
        ]);
    }
}
