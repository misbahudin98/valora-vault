<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientProjectRequest;
use App\Http\Requests\Admin\UpdateClientProjectRequest;
use App\Models\Client;
use App\Models\ClientProject;
use App\Services\ClientProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientProjectController extends Controller
{
    /**
     * List all client projects with multi-criteria search and aggregation stats.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ClientProject::class);

        $query = ClientProject::with(['client', 'roles.menus', 'databaseTables', 'leadPicUser', 'workers']);

        // Search filter with wildcard sanitization (Anti-DoS)
        if ($search = $request->input('search')) {
            $escaped = addcslashes(trim((string) $search), '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('app_name', 'like', "%{$escaped}%")
                  ->orWhere('project_name', 'like', "%{$escaped}%")
                  ->orWhere('lead_pic', 'like', "%{$escaped}%")
                  ->orWhereHas('client', function ($cq) use ($escaped) {
                      $cq->where('name', 'like', "%{$escaped}%")
                         ->orWhere('industry', 'like', "%{$escaped}%")
                         ->orWhere('contact_person', 'like', "%{$escaped}%");
                  });
            });
        }

        // Project Type filter
        if ($type = $request->input('project_type')) {
            if (in_array($type, ['development', 'maintenance'], true)) {
                $query->where('project_type', $type);
            }
        }

        // Status filter
        if ($status = $request->input('status')) {
            if (in_array($status, ['Aktif', 'Selesai', 'Pending'], true)) {
                $query->where('status', $status);
            }
        }

        // Safe query ceiling limit (default 1000, max 5000)
        $limit = min((int) $request->input('limit', 1000), 5000);
        $projects = $query->orderBy('id', 'desc')->limit($limit)->get();

        // Calculate KPI summary metrics
        $totalProjects = ClientProject::count();
        $totalClients = Client::count();
        $activeProjects = ClientProject::where('status', 'Aktif')->count();
        $devProjects = ClientProject::where('project_type', 'development')->count();
        $maintProjects = ClientProject::where('project_type', 'maintenance')->count();
        $totalValuation = (float) ClientProject::sum('grand_total');
        $totalPaid = (float) ClientProject::sum('paid_amount');

        return response()->json([
            'data' => $projects,
            'meta' => [
                'total_projects' => $totalProjects,
                'total_clients' => $totalClients,
                'active_projects' => $activeProjects,
                'development_projects' => $devProjects,
                'maintenance_projects' => $maintProjects,
                'total_valuation' => $totalValuation,
                'total_paid' => $totalPaid,
                'limit' => $limit,
            ]
        ], 200);
    }

    /**
     * Show single project details.
     */
    public function show(int $id): JsonResponse
    {
        $project = ClientProject::with(['client', 'roles.menus', 'databaseTables', 'leadPicUser', 'workers'])->findOrFail($id);
        Gate::authorize('view', $project);

        return response()->json([
            'data' => $project,
        ], 200);
    }

    /**
     * Store a newly created project with optional new client intake.
     */
    public function store(StoreClientProjectRequest $request, ClientProjectService $service): JsonResponse
    {
        $project = $service->createProject($request->validated());

        return response()->json([
            'message' => 'Proyek klien berhasil didaftarkan.',
            'data' => $project,
        ], 201);
    }

    /**
     * Update the specified client project.
     */
    public function update(UpdateClientProjectRequest $request, int $id, ClientProjectService $service): JsonResponse
    {
        $project = ClientProject::findOrFail($id);
        Gate::authorize('update', $project);

        $updatedProject = $service->updateProject($project, $request->validated());

        return response()->json([
            'message' => 'Data proyek berhasil diperbarui.',
            'data' => $updatedProject,
        ], 200);
    }

    /**
     * Delete the specified client project (only allowed if project is unpaid).
     */
    public function destroy(int $id, ClientProjectService $service): JsonResponse
    {
        $project = ClientProject::findOrFail($id);
        Gate::authorize('delete', $project);

        if ((float) $project->paid_amount > 0 || $project->payments()->exists()) {
            return response()->json([
                'type' => 'https://tools.ietf.org/html/rfc7807',
                'title' => 'Proyek Tidak Dapat Dihapus',
                'status' => 422,
                'detail' => 'Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus.',
            ], 422);
        }

        $service->deleteProject($project);

        return response()->json([
            'message' => 'Proyek berhasil dihapus.',
        ], 200);
    }

    /**
     * Export client projects to CSV with UTF-8 BOM and formula injection protection (CWE-1236).
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', ClientProject::class);

        $filename = 'valora_klien_proyek_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write UTF-8 BOM for Microsoft Excel / Google Sheets compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'ID Proyek',
                'Nama Klien',
                'Nama Aplikasi',
                'Tipe Proyek',
                'Industri',
                'Lead PIC',
                'Kontak',
                'Status',
                'Nilai Valuasi (IDR)',
                'Sudah Dibayar (IDR)',
                'Sisa Tagihan (IDR)',
                'Tanggal Dibuat',
            ]);

            // Query in chunks of 500 to maintain constant memory O(1)
            ClientProject::with('client')
                ->orderBy('id', 'desc')
                ->chunk(500, function ($projects) use ($handle) {
                    foreach ($projects as $p) {
                        $clientName = $p->client->name ?? '-';
                        $industry = $p->client->industry ?? '-';
                        $contact = $p->client->phone ?? $p->client->contact_person ?? '-';
                        $sisa = max(0, (float) $p->grand_total - (float) $p->paid_amount);

                        // CWE-1236 Formula Injection Sanitizer
                        $sanitize = function (?string $val): string {
                            if ($val === null || $val === '') {
                                return '';
                            }
                            if (in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                                return "'" . $val;
                            }
                            return $val;
                        };

                        fputcsv($handle, [
                            $p->id,
                            $sanitize($clientName),
                            $sanitize($p->app_name),
                            $p->project_type,
                            $sanitize($industry),
                            $sanitize($p->lead_pic),
                            $sanitize($contact),
                            $p->status,
                            number_format((float) $p->grand_total, 2, '.', ''),
                            number_format((float) $p->paid_amount, 2, '.', ''),
                            number_format($sisa, 2, '.', ''),
                            $p->created_at ? $p->created_at->format('Y-m-d H:i:s') : '-',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
