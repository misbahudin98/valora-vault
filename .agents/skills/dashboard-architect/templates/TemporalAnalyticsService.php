<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service Agregasi Analitik Multi-Temporal (dashboard-architect)
 * Standard: Ralph Kimball (Periodic Snapshot Rollups), Plafon Aman limit(1000).
 */
final class TemporalAnalyticsService
{
    private const SAFE_CEILING_LIMIT = 1000;

    /**
     * Mengambil data analitik terstruktur berdasarkan horizon waktu.
     *
     * @param string $horizon 'daily'|'weekly'|'monthly'|'annual'|'five_year'|'academic_year'
     * @param string $unit 'all'|'pesantren'|'ltq'|'pkbm'
     * @param array{start_date?: string, end_date?: string, academic_year?: string, year?: int} $params
     * @return array<string, mixed>
     */
    public function getAnalytics(string $horizon, string $unit = 'all', array $params = []): array
    {
        return match ($horizon) {
            'daily' => $this->resolveDailyGranular($unit, $params['start_date'] ?? date('Y-m-d')),
            'weekly' => $this->resolveWeeklyRolling($unit, $params['start_date'] ?? date('Y-m-d')),
            'monthly' => $this->resolveMonthlySnapshots($unit, (int) ($params['year'] ?? date('Y'))),
            'annual' => $this->resolveAnnualComparisons($unit),
            'five_year' => $this->resolveFiveYearCohort($unit),
            'academic_year' => $this->resolveAcademicYearCycle($unit, $params['academic_year'] ?? '2026/2027'),
            default => throw new InvalidArgumentException("Horizon temporal tidak dikenali: {$horizon}"),
        };
    }

    /**
     * 1. Horizon Harian: Agregasi On-The-Fly per sesi sholat/kegiatan.
     */
    private function resolveDailyGranular(string $unit, string $date): array
    {
        $raw = DB::table('presensi_transaksional as pt')
            ->join('santri as s', 's.id', '=', 'pt.santri_id')
            ->where('pt.tanggal', $date)
            ->when($unit !== 'all', fn($q) => $q->where('s.unit_sekolah', $unit))
            ->select([
                'pt.sesi',
                DB::raw("COUNT(*) as total_record"),
                DB::raw("SUM(CASE WHEN pt.status = 'H' THEN 1 ELSE 0 END) as hadir"),
                DB::raw("SUM(CASE WHEN pt.status = 'S' THEN 1 ELSE 0 END) as sakit"),
                DB::raw("SUM(CASE WHEN pt.status = 'I' THEN 1 ELSE 0 END) as izin"),
                DB::raw("SUM(CASE WHEN pt.status = 'A' THEN 1 ELSE 0 END) as alpa"),
                DB::raw("SUM(CASE WHEN pt.status = 'M' THEN 1 ELSE 0 END) as mamnu"),
            ])
            ->groupBy('pt.sesi')
            ->orderBy('pt.sesi')
            ->limit(self::SAFE_CEILING_LIMIT)
            ->get();

        $totalRecords = (int) $raw->sum('total_record');
        $totalHadir = (int) $raw->sum('hadir');

        return [
            'horizon' => 'daily',
            'target_date' => $date,
            'unit' => $unit,
            'sessions' => $raw,
            'summary' => [
                'total_records' => $totalRecords,
                'total_hadir' => $totalHadir,
                'total_alpa' => (int) $raw->sum('alpa'),
                'attendance_rate' => $totalRecords > 0
                    ? round(($totalHadir / $totalRecords) * 100, 2)
                    : 0.0,
            ],
        ];
    }

    /**
     * 2. Horizon Mingguan: 7 Hari Terakhir berjalan (Rolling 7-day trend).
     */
    private function resolveWeeklyRolling(string $unit, string $endDate): array
    {
        $startDate = date('Y-m-d', strtotime("{$endDate} -6 days"));

        $trend = DB::table('presensi_transaksional as pt')
            ->join('santri as s', 's.id', '=', 'pt.santri_id')
            ->whereBetween('pt.tanggal', [$startDate, $endDate])
            ->when($unit !== 'all', fn($q) => $q->where('s.unit_sekolah', $unit))
            ->select([
                'pt.tanggal',
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN pt.status = 'H' THEN 1 ELSE 0 END) as hadir"),
                DB::raw("ROUND(SUM(CASE WHEN pt.status = 'H' THEN 1.0 ELSE 0.0 END) / COUNT(*) * 100, 2) as rate"),
            ])
            ->groupBy('pt.tanggal')
            ->orderBy('pt.tanggal', 'ASC')
            ->limit(7)
            ->get();

        return [
            'horizon' => 'weekly',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'trend' => $trend,
            'average_rate' => round((float) $trend->avg('rate'), 2),
        ];
    }

    /**
     * 3. Horizon Bulanan: 12 titik snapshot bulanan dari tabel Rollup.
     */
    private function resolveMonthlySnapshots(string $unit, int $year): array
    {
        $records = DB::table('presensi_temporal_rollups')
            ->where('horizon', 'monthly')
            ->where('unit_tugas', $unit)
            ->where('horizon_key', 'LIKE', "{$year}-%")
            ->orderBy('horizon_key', 'ASC')
            ->limit(12)
            ->get();

        return [
            'horizon' => 'monthly',
            'year' => $year,
            'unit' => $unit,
            'months' => $records,
            'annual_aggregate_rate' => round((float) $records->avg('persentase_kehadiran'), 2),
        ];
    }

    /**
     * 4. Horizon Tahunan: Komparasi 3 Tahun Terakhir Kalender Masehi (YoY).
     */
    private function resolveAnnualComparisons(string $unit): array
    {
        $currentYear = (int) date('Y');
        $years = [(string) ($currentYear - 2), (string) ($currentYear - 1), (string) $currentYear];

        $records = DB::table('presensi_temporal_rollups')
            ->where('horizon', 'annual')
            ->where('unit_tugas', $unit)
            ->whereIn('horizon_key', $years)
            ->orderBy('horizon_key', 'ASC')
            ->limit(5)
            ->get();

        return [
            'horizon' => 'annual',
            'unit' => $unit,
            'years' => $records,
        ];
    }

    /**
     * 5. Horizon Per 5 Tahunan: Tren Makro Rencana Strategis (Renstra).
     */
    private function resolveFiveYearCohort(string $unit): array
    {
        $cohorts = DB::table('presensi_temporal_rollups')
            ->where('horizon', 'five_year')
            ->where('unit_tugas', $unit)
            ->orderBy('horizon_key', 'DESC')
            ->limit(5)
            ->get();

        return [
            'horizon' => 'five_year',
            'unit' => $unit,
            'cohorts' => $cohorts,
        ];
    }

    /**
     * 6. Horizon Tahun Ajaran (Domain Spesifik: Juli N s/d Juni N+1).
     */
    private function resolveAcademicYearCycle(string $unit, string $academicYear): array
    {
        $rollups = DB::table('presensi_temporal_rollups')
            ->where('horizon', 'academic_year')
            ->where('horizon_key', $academicYear)
            ->where('unit_tugas', $unit)
            ->first();

        $semesterBreakdown = DB::table('dim_kalender_akademik as d')
            ->join('presensi_transaksional as p', 'p.tanggal', '=', 'd.tanggal')
            ->join('santri as s', 's.id', '=', 'p.santri_id')
            ->where('d.tahun_ajaran', $academicYear)
            ->when($unit !== 'all', fn($q) => $q->where('s.unit_sekolah', $unit))
            ->select([
                'd.semester_akademik',
                DB::raw("COUNT(*) as total_presensi"),
                DB::raw("SUM(CASE WHEN p.status = 'H' THEN 1 ELSE 0 END) as total_hadir"),
                DB::raw("ROUND(SUM(CASE WHEN p.status = 'H' THEN 1.0 ELSE 0.0 END) / COUNT(*) * 100, 2) as semester_rate"),
            ])
            ->groupBy('d.semester_akademik')
            ->orderBy('d.semester_akademik', 'ASC')
            ->get();

        return [
            'horizon' => 'academic_year',
            'academic_year' => $academicYear,
            'unit' => $unit,
            'overall' => $rollups,
            'semesters' => $semesterBreakdown,
        ];
    }
}
