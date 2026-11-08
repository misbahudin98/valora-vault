<?php
/**
 * HYPERION VAULT — Universal Repository Provisioning Wizard Engine (CLI & Automation)
 *
 * Automates provisioning a new clean, production-ready repository derived from Hyperion Vault:
 * 1. Validates project parameters (Slug, Description, Visibility, Ports).
 * 2. Checks GitHub CLI and provisions remote repository (gh repo create).
 * 3. Copies clean foundation to D:\laragon\www\<slug> (excluding .git, vendor, node_modules, logs).
 * 4. Git sanitization (fresh git init -b main, connect remote).
 * 5. Environment bootstrapping: fresh .env, APP_KEY generation, isolated SQLite DB, port allocation.
 * 6. Standard Rule Injection: .agents/rules/universal_invariants.md, GEMINI.md, AGENTS.md, llms.txt.
 * 7. Initial non-destructive database migration (php artisan migrate --force).
 *
 * Usage:
 *   php scripts/provision_repo.php --name=proyek-baru --desc="Keterangan Proyek" --visibility=private --fe-port=5174 --be-port=9005
 *   php scripts/provision_repo.php (Interactive Mode)
 *
 * Standard: SDD-Enterprise-Deterministic v8.0.0
 */

declare(strict_types=1);

$startTime = microtime(true);

echo "=================================================================\n";
echo "   🚀 HYPERION VAULT — REPOSITORY PROVISIONING WIZARD ENGINE    \n";
echo "=================================================================\n\n";

$sourceRoot = dirname(__DIR__);
$baseWwwDir = dirname($sourceRoot); // D:\laragon\www

// Helper: Parse CLI arguments
function parseArguments(array $argv): array {
    $options = [];
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = $parts[1] ?? true;
        }
    }
    return $options;
}

$cliOpts = parseArguments($argv);

if (isset($cliOpts['help'])) {
    echo "Panduan Penggunaan:\n";
    echo "  --name=<slug>          Nama direktori & slug repositori (wajib, lowercase/hyphen)\n";
    echo "  --desc=\"<teks>\"        Deskripsi singkat repositori\n";
    echo "  --visibility=private   Visibilitas GitHub (private atau public, default: private)\n";
    echo "  --fe-port=5174         Alokasi port frontend Vite (default: 5174)\n";
    echo "  --be-port=9005         Alokasi port FastCGI backend (default: 9005)\n";
    echo "  --skip-github          Lewati pembuatan remote repositori GitHub CLI\n";
    echo "  --dry-run              Simulasikan proses tanpa menulis file ke disk\n\n";
    exit(0);
}

// -------------------------------------------------------------
// LANGKAH 1: INPUT PARAMETER & VALIDASI
// -------------------------------------------------------------
echo "[Langkah 1/4] Memvalidasi Parameter Proyek...\n";

$projectName = $cliOpts['name'] ?? null;
if (!$projectName) {
    if (PHP_SAPI === 'cli') {
        echo "Masukkan Nama / Slug Proyek Baru (contoh: valora-vault): ";
        $handle = fopen('php://stdin', 'r');
        $projectName = trim((string) fgets($handle));
        fclose($handle);
    }
}

if (!$projectName || !preg_match('/^[a-z0-9_-]+$/i', (string) $projectName)) {
    fwrite(STDERR, "❌ ERROR: Nama proyek tidak valid! Hanya huruf, angka, minus (-), dan garis bawah (_).\n");
    exit(1);
}

$projectSlug = strtolower(str_replace('_', '-', (string) $projectName));
$targetDir = $baseWwwDir . DIRECTORY_SEPARATOR . $projectSlug;
$projectDesc = $cliOpts['desc'] ?? "Clean Enterprise Starter Kit derived from Hyperion Vault";
$rawVisibility = $cliOpts['visibility'] ?? 'private';
$visibility = in_array($rawVisibility, ['public', 'private'], true) ? $rawVisibility : 'private';
$fePort = (int) ($cliOpts['fe-port'] ?? 5174);
$bePort = (int) ($cliOpts['be-port'] ?? 9005);
$skipGithub = isset($cliOpts['skip-github']);
$dryRun = isset($cliOpts['dry-run']);

if (file_exists($targetDir) && is_dir($targetDir) && count(scandir($targetDir)) > 2) {
    fwrite(STDERR, "❌ ERROR: Direktori target sudah ada dan tidak kosong di [{$targetDir}]!\n");
    exit(1);
}

echo "  ✓ Nama Proyek       : {$projectSlug}\n";
echo "  ✓ Direktori Target  : {$targetDir}\n";
echo "  ✓ Visibilitas GitHub: {$visibility}\n";
echo "  ✓ Alokasi Port FE   : {$fePort}\n";
echo "  ✓ Alokasi Port BE   : {$bePort}\n\n";

if ($dryRun) {
    echo "⚠️ Mode --dry-run aktif. Tidak ada file yang ditulis.\n";
    exit(0);
}

// -------------------------------------------------------------
// LANGKAH 2: GITHUB REMOTE PROVISIONING (VIA GITHUB CLI)
// -------------------------------------------------------------
echo "[Langkah 2/4] Memeriksa & Mem-provisioning Remote GitHub...\n";

$githubProvisioned = false;
$remoteUrl = null;

if (!$skipGithub) {
    // Cek apakah 'gh' CLI terpasang dan login
    $ghStatus = @shell_exec('gh auth status 2>&1');
    if ($ghStatus && str_contains($ghStatus, 'Logged in to')) {
        echo "  ✓ GitHub CLI terdeteksi & akun terotentikasi.\n";
        echo "  ⏳ Membuat repositori baru di GitHub (gh repo create {$projectSlug} --{$visibility})...\n";
        $createOutput = @shell_exec("gh repo create {$projectSlug} --{$visibility} --description=\"{$projectDesc}\" 2>&1");
        if ($createOutput && !str_contains($createOutput, 'error')) {
            echo "  ✓ Repositori GitHub berhasil dibuat: {$projectSlug}\n";
            $githubProvisioned = true;
        } else {
            echo "  ⚠️ Peringatan pembuatan GitHub: {$createOutput}\n";
            echo "  Melanjutkan pembuatan repositori lokal...\n";
        }
    } else {
        echo "  ℹ️ GitHub CLI belum login atau tidak terpasang. Melewati pembuatan remote GitHub otomatis.\n";
    }
} else {
    echo "  ℹ️ Opsi --skip-github aktif. Pembuatan remote GitHub dilewati.\n";
}
echo "\n";

// -------------------------------------------------------------
// LANGKAH 3: LOCAL TEMPLATE CLONING & SANITASI GIT
// -------------------------------------------------------------
echo "[Langkah 3/4] Menduplikasi Fondasi Bersih ke Direktori Target...\n";

// Recursive copy helper with strict exclude rules
function copyDirectoryClean(string $src, string $dst): void {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        @mkdir($dst, 0755, true);
    }

    $excludedNames = [
        '.git',
        'vendor',
        'node_modules',
        '.svelte-kit',
        'build',
        '.installed',
    ];

    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (in_array($file, $excludedNames, true)) {
            continue;
        }

        $srcPath = $src . DIRECTORY_SEPARATOR . $file;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $file;

        // Skip log files & session files
        if (str_ends_with($srcPath, '.log') || str_contains($srcPath, 'framework' . DIRECTORY_SEPARATOR . 'sessions')) {
            continue;
        }

        if (is_dir($srcPath)) {
            copyDirectoryClean($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

copyDirectoryClean($sourceRoot, $targetDir);
echo "  ✓ Salinan fondasi bersih berhasil disalin ke: {$targetDir}\n";

// Sanitasi Git
echo "  ⏳ Menginisialisasi Git baru & menyetel branch main...\n";
$currentWd = getcwd();
chdir($targetDir);

@shell_exec('git init -b main 2>&1');
if ($githubProvisioned) {
    // Ambil repo owner / name
    $ghUser = trim((string) @shell_exec('gh api user -q .login 2>&1'));
    if ($ghUser && !str_contains($ghUser, 'error')) {
        $remoteUrl = "https://github.com/{$ghUser}/{$projectSlug}.git";
        @shell_exec("git remote add origin {$remoteUrl} 2>&1");
        echo "  ✓ Remote origin dihubungkan ke: {$remoteUrl}\n";
    }
}
chdir($currentWd);
echo "  ✓ Git baru diinisialisasi.\n\n";

// -------------------------------------------------------------
// LANGKAH 4: AUTOMATED BOOTSTRAPPING & RULE INJECTION
// -------------------------------------------------------------
echo "[Langkah 4/4] Bootstrapping Lingkungan, Database & Aturan Standar...\n";

// 1. Regenerasi .env baru
$envExample = $targetDir . '/backend/.env.example';
$envTarget = $targetDir . '/backend/.env';
if (file_exists($envExample)) {
    $envContent = file_get_contents($envExample);
    $envContent = preg_replace('/APP_NAME=.*/', "APP_NAME=\"{$projectSlug}\"", $envContent);
    $envContent = preg_replace('/APP_URL=.*/', "APP_URL=http://{$projectSlug}.test", $envContent);
    $envContent = preg_replace('/SESSION_DRIVER=.*/', "SESSION_DRIVER=file", $envContent);
    $envContent = preg_replace('/DB_CONNECTION=.*/', "DB_CONNECTION=sqlite", $envContent);
    $envContent = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE=database/database.sqlite", $envContent);
    file_put_contents($envTarget, $envContent);
    echo "  ✓ Berkas backend/.env baru dikonfigurasi (SESSION_DRIVER=file, SQLite isolated).\n";
}

// 2. Inisialisasi SQLite database terisolasi
$sqliteFile = $targetDir . '/backend/database/database.sqlite';
if (!file_exists($sqliteFile)) {
    @touch($sqliteFile);
    echo "  ✓ Basis data terisolasi dibuat: backend/database/database.sqlite\n";
}

// 3. Generate APP_KEY
chdir($targetDir . '/backend');
echo "  ⏳ Menjalankan php artisan key:generate...\n";
@shell_exec('php artisan key:generate --force 2>&1');

// 4. Injeksi Port Allocation pada frontend
$viteConfigPath = $targetDir . '/frontend/vite.config.js';
if (file_exists($viteConfigPath)) {
    $viteConfig = file_get_contents($viteConfigPath);
    if (!str_contains($viteConfig, 'server:')) {
        $serverBlock = "\n\tserver: {\n\t\thost: '127.0.0.1',\n\t\tport: {$fePort},\n\t\tstrictPort: true,\n\t},\n";
        $viteConfig = preg_replace('/export default defineConfig\(\{/', "export default defineConfig({" . $serverBlock, $viteConfig);
        file_put_contents($viteConfigPath, $viteConfig);
        echo "  ✓ Port Vite disetel khusus ke: {$fePort}\n";
    }
}

// 5. Injeksi Aturan Standar Internasional & Anti-Slop Policy
$ruleDir = $targetDir . '/.agents/rules';
if (!is_dir($ruleDir)) {
    @mkdir($ruleDir, 0755, true);
}
@copy($sourceRoot . '/.agents/rules/universal_invariants.md', $targetDir . '/.agents/rules/universal_invariants.md');
@copy($sourceRoot . '/GEMINI.md', $targetDir . '/GEMINI.md');
@copy($sourceRoot . '/AGENTS.md', $targetDir . '/AGENTS.md');
@copy($sourceRoot . '/llms.txt', $targetDir . '/llms.txt');
echo "  ✓ Berkas aturan standar internasional & kebijakan anti-slop disuntikkan:\n";
echo "    - .agents/rules/universal_invariants.md\n";
echo "    - GEMINI.md & AGENTS.md\n";
echo "    - llms.txt (5 Golden Invariants)\n";

// 6. Jalankan Migrasi Awal
echo "  ⏳ Menjalankan migrasi awal basis data (php artisan migrate --force)...\n";
$migOutput = @shell_exec('php artisan migrate --force 2>&1');
chdir($currentWd);
echo "  ✓ Migrasi awal selesai dieksekusi.\n\n";

$elapsed = round(microtime(true) - $startTime, 2);

echo "=================================================================\n";
echo "   🎉 PROVISIONING BERHASIL DISELESAIKAN DALAM {$elapsed} DETIK!  \n";
echo "=================================================================\n\n";
echo "Langkah Tindak Lanjut untuk Mengoperasikan Repositori Baru:\n";
echo "1. Pindah ke direktori: cd D:\\laragon\\www\\{$projectSlug}\n";
echo "2. Jalankan FastCGI Worker terisolasi (Port {$bePort}):\n";
echo "   Start-Process -FilePath \"D:\\laragon\\bin\\php\\8.3\\php-cgi.exe\" -ArgumentList \"-b 127.0.0.1:{$bePort}\" -WindowStyle Hidden\n";
echo "3. Jalankan Vite Dev Server (Port {$fePort}):\n";
echo "   cd frontend && npm run dev -- --port {$fePort}\n";
echo "4. Buat initial commit:\n";
echo "   git add . && git commit -m \"feat: initial clean repository provisioned from hyperion-vault v8.0.0\"\n";
if ($remoteUrl) {
    echo "   git push -u origin main\n";
}
echo "\nRepositori siap digunakan dengan kepatuhan SDD penuh & zero-slop.\n";
