<?php

use App\Models\UserDevice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('session:prune-files {--grace=60 : Waktu tenggang (menit) untuk file sesi unauthenticated} {--force : Hapus paksa seluruh file sesi yang tidak terindeks}', function () {
    $grace = (int) $this->option('grace');
    $force = (bool) $this->option('force');
    $this->info("Memulai pembersihan direktori storage/framework/sessions...");

    $result = UserDevice::pruneDiskSessions($grace, $force);
    $kb = round($result['bytes_freed'] / 1024, 2);

    $this->info("Pemeriksaan selesai: {$result['scanned']} berkas diperiksa.");
    $this->info("Berkas dihapus: {$result['deleted']} berkas ({$kb} KB ruang disk dipulihkan).");
    $this->info("Sesi aktif dipertahankan: {$result['active_retained']} berkas.");
})->purpose('Membersihkan akumulasi file sesi kadaluarsa dan orphan di storage/framework/sessions');

Schedule::command('session:prune-files --grace=60')->hourly();

Artisan::command('version:sync {action=status : Action to perform: status, bump, set} {type=patch : For bump: patch, minor, major; For set: version number} {--changelog= : Changelog description note}', function () {
    $action = $this->argument('action');
    $type = $this->argument('type');
    $changelog = $this->option('changelog');

    $scriptPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . 'version_sync.php';
    if (!file_exists($scriptPath)) {
        $this->error("Script doc/version_sync.php tidak ditemukan!");
        return 1;
    }

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($action);
    if ($action === 'bump' || $action === 'set') {
        $cmd .= ' ' . escapeshellarg($type);
    }
    if ($changelog) {
        $cmd .= ' --changelog=' . escapeshellarg($changelog);
    }

    passthru($cmd, $exitCode);
    return $exitCode;
})->purpose('Sinkronisasi SemVer 2.0.0 dan dokumentasi serentak lintas 9 berkas repositori');
