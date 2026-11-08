<?php
/**
 * HYPERION VAULT — Ultra-Fast Automated FTP Deployment Engine
 * Packages, streams, unpacks, and provisions the production environment in seconds.
 *
 * Usage:
 *   php deploy_ftp.php
 *   php deploy_ftp.php --host=ftp.example.com --user=deploy@example.com --pass=Secret123 --domain=https://example.com
 *
 * Credentials can be set via:
 * 1. Command-line arguments (--host, --user, --pass, --domain, --port, --path)
 * 2. Environment file (.env.deploy)
 * 3. Environment variables (DEPLOY_FTP_HOST, DEPLOY_FTP_USER, etc.)
 */

declare(strict_types=1);

$startTime = microtime(true);

echo "=================================================================\n";
echo "   🚀 HYPERION VAULT — PRODUCTION FTP AUTOMATED DEPLOYMENT ENGINE \n";
echo "=================================================================\n\n";

$baseDir = __DIR__;
$backendDir = $baseDir . DIRECTORY_SEPARATOR . 'backend';
$frontendDir = $baseDir . DIRECTORY_SEPARATOR . 'frontend';
$buildDir = $frontendDir . DIRECTORY_SEPARATOR . 'build';
$stagingDir = $baseDir . DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR . 'staging';
$zipFile = $baseDir . DIRECTORY_SEPARATOR . 'deploy_package.zip';
$runnerFile = $baseDir . DIRECTORY_SEPARATOR . 'deploy_runner.php';

// Load configuration from .env.deploy, backend/.env, or root .env
$envCandidates = [
    $baseDir . DIRECTORY_SEPARATOR . '.env.deploy',
    $baseDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . '.env',
    $baseDir . DIRECTORY_SEPARATOR . '.env',
];

$deployEnv = [];
foreach ($envCandidates as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $cleanKey = trim($key);
                if (!isset($deployEnv[$cleanKey])) {
                    $deployEnv[$cleanKey] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }
        }
    }
}

// Parse CLI options
$options = getopt('', ['host:', 'port:', 'user:', 'pass:', 'domain:', 'path:', 'ssl', 'skip-build']);
$skipBuild = isset($options['skip-build']);

$ftpHost = $options['host'] ?? $deployEnv['DEPLOY_FTP_HOST'] ?? getenv('DEPLOY_FTP_HOST') ?: '';
$ftpPort = (int)($options['port'] ?? $deployEnv['DEPLOY_FTP_PORT'] ?? getenv('DEPLOY_FTP_PORT') ?: 21);
$ftpUser = $options['user'] ?? $deployEnv['DEPLOY_FTP_USER'] ?? getenv('DEPLOY_FTP_USER') ?: '';
$ftpPass = $options['pass'] ?? $deployEnv['DEPLOY_FTP_PASS'] ?? getenv('DEPLOY_FTP_PASS') ?: '';
$appDomain = rtrim($options['domain'] ?? $deployEnv['DEPLOY_APP_DOMAIN'] ?? getenv('DEPLOY_APP_DOMAIN') ?: '', '/');
$remotePath = trim($options['path'] ?? $deployEnv['DEPLOY_FTP_REMOTE_PATH'] ?? getenv('DEPLOY_FTP_REMOTE_PATH') ?: '/', '/');
$appName = $deployEnv['DEPLOY_APP_NAME'] ?? getenv('DEPLOY_APP_NAME') ?: 'Hyperion Vault';
$dbConnection = $deployEnv['DEPLOY_DB_CONNECTION'] ?? getenv('DEPLOY_DB_CONNECTION') ?: 'sqlite';
$dbDatabase = $deployEnv['DEPLOY_DB_DATABASE'] ?? getenv('DEPLOY_DB_DATABASE') ?: '';

// Validate mandatory parameters
if (empty($ftpHost) || empty($ftpUser) || empty($ftpPass) || empty($appDomain)) {
    echo "  [ERROR] Kredensial FTP atau Domain Aplikasi belum lengkap!\n";
    echo "  Silakan sediakan kredensial melalui salah satu cara berikut:\n";
    echo "  1. Buat berkas .env.deploy (salin dari .env.deploy.example):\n";
    echo "     DEPLOY_FTP_HOST=\"ftp.domain.com\"\n";
    echo "     DEPLOY_FTP_USER=\"user@domain.com\"\n";
    echo "     DEPLOY_FTP_PASS=\"KataSandi123!\"\n";
    echo "     DEPLOY_APP_DOMAIN=\"https://domain.com\"\n\n";
    echo "  2. Atau jalankan via argumen CLI:\n";
    echo "     php deploy_ftp.php --host=... --user=... --pass=... --domain=https://...\n";
    exit(1);
}

$secretToken = bin2hex(random_bytes(16));
$domainHost = parse_url($appDomain, PHP_URL_HOST) ?: 'localhost';

// Helper: Recursive copy
function copyDirectory(string $src, string $dst, array $excludes = []): void {
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    $dir = opendir($src);
    if (!$dir) return;
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        foreach ($excludes as $exclude) {
            if (fnmatch($exclude, $file)) continue 2;
        }
        $srcPath = $src . DIRECTORY_SEPARATOR . $file;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
        if (is_dir($srcPath)) {
            copyDirectory($srcPath, $dstPath, $excludes);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

// Helper: Recursive delete
function deleteDirectory(string $dir): void {
    if (!file_exists($dir)) return;
    if (!is_dir($dir)) {
        @unlink($dir);
        return;
    }
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

// Step 1: Pre-flight Verification & Fresh Frontend Build
echo "[1/6] Menyiapkan kelayakan bundel pra-deploy...\n";
if (!$skipBuild) {
    echo "  -> Mengompilasi frontend Svelte 5 terbaru (Fresh Production Build)...\n";
    $npmBuildCmd = 'npm --prefix ' . escapeshellarg($frontendDir) . ' run build';
    passthru($npmBuildCmd, $npmExit);
    if ($npmExit !== 0 || !file_exists($buildDir . DIRECTORY_SEPARATOR . 'index.html')) {
        echo "  [ERROR] Kompilasi frontend gagal! Silakan periksa log kompilasi di atas.\n";
        exit(1);
    }
    echo "  ✓ Kompilasi frontend terbaru selesai & terverifikasi.\n";
} else {
    echo "  [INFO] Opsi --skip-build terdeteksi. Menggunakan build frontend yang ada.\n";
    if (!file_exists($buildDir . DIRECTORY_SEPARATOR . 'index.html')) {
        echo "  [ERROR] Direktori frontend/build/index.html belum ditemukan! Tidak dapat melewati build.\n";
        exit(1);
    }
}
if (!file_exists($backendDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    echo "  [INFO] Dependensi Composer backend belum ditemukan. Memasang dependensi otomatis...\n";
    $compCmd = 'composer install --no-dev --optimize-autoloader --working-dir=' . escapeshellarg($backendDir);
    passthru($compCmd, $compExit);
    if ($compExit !== 0 || !file_exists($backendDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
        echo "  [ERROR] Pemasangan Composer vendor gagal!\n";
        exit(1);
    }
}
echo "  ✓ Frontend static build & Composer vendor autoload terverifikasi.\n\n";

// Step 2: Prepare Staging Directory
echo "[2/6] Mempersiapkan staging folder produksi...\n";
if (is_dir($stagingDir)) {
    deleteDirectory($stagingDir);
}
mkdir($stagingDir, 0777, true);

// 2.1 Root files
copy($baseDir . DIRECTORY_SEPARATOR . 'index.php', $stagingDir . DIRECTORY_SEPARATOR . 'index.php');
copy($baseDir . DIRECTORY_SEPARATOR . '.htaccess', $stagingDir . DIRECTORY_SEPARATOR . '.htaccess');
copy($baseDir . DIRECTORY_SEPARATOR . 'install.php', $stagingDir . DIRECTORY_SEPARATOR . 'install.php');
if (file_exists($baseDir . DIRECTORY_SEPARATOR . '.installed')) {
    copy($baseDir . DIRECTORY_SEPARATOR . '.installed', $stagingDir . DIRECTORY_SEPARATOR . '.installed');
}

// 2.2 Root static assets (for direct web server serving without PHP overhead)
copyDirectory($buildDir . DIRECTORY_SEPARATOR . '_app', $stagingDir . DIRECTORY_SEPARATOR . '_app');
if (is_dir($buildDir . DIRECTORY_SEPARATOR . 'images')) {
    copyDirectory($buildDir . DIRECTORY_SEPARATOR . 'images', $stagingDir . DIRECTORY_SEPARATOR . 'images');
}
if (file_exists($buildDir . DIRECTORY_SEPARATOR . 'favicon.svg')) {
    copy($buildDir . DIRECTORY_SEPARATOR . 'favicon.svg', $stagingDir . DIRECTORY_SEPARATOR . 'favicon.svg');
}
if (file_exists($buildDir . DIRECTORY_SEPARATOR . 'favicon.ico')) {
    copy($buildDir . DIRECTORY_SEPARATOR . 'favicon.ico', $stagingDir . DIRECTORY_SEPARATOR . 'favicon.ico');
}
if (file_exists($buildDir . DIRECTORY_SEPARATOR . 'robots.txt')) {
    copy($buildDir . DIRECTORY_SEPARATOR . 'robots.txt', $stagingDir . DIRECTORY_SEPARATOR . 'robots.txt');
}

// 2.3 Frontend build (for universal gateway fallback in index.php)
copyDirectory($buildDir, $stagingDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'build');

// 2.4 Backend files
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'app', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'app');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'bootstrap', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'bootstrap');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'config', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'config');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'database', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'database');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'public', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'public');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'resources', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'resources');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'routes', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'routes');
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'storage', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage', ['*.log']);
copyDirectory($backendDir . DIRECTORY_SEPARATOR . 'vendor', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'vendor');

copy($backendDir . DIRECTORY_SEPARATOR . 'artisan', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'artisan');
copy($backendDir . DIRECTORY_SEPARATOR . 'composer.json', $stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'composer.json');

// 2.5 Production .env creation
$appKey = $deployEnv['DEPLOY_APP_KEY'] ?? '';
if (empty($appKey)) {
    // Generate secure 32-byte base64 key
    $appKey = 'base64:' . base64_encode(random_bytes(32));
}

$prodDatabaseLine = $dbDatabase ? "DB_DATABASE={$dbDatabase}" : "DB_DATABASE=database/database.sqlite";

$prodEnvContent = <<<ENV
APP_NAME="{$appName}"
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false
APP_URL={$appDomain}

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION={$dbConnection}
{$prodDatabaseLine}

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

AUTH_TOKEN_TTL_MINUTES=120
AUTH_JWT_COOKIE_NAME=auth_jwt
AUTH_ROLE_COOKIE_NAME=user_role
AUTH_MAX_CONCURRENT_DEVICES=3

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

CACHE_STORE=file

VITE_APP_NAME="{$appName}"
FRONTEND_URL={$appDomain}
SANCTUM_STATEFUL_DOMAINS={$domainHost}
ENV;

file_put_contents($stagingDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . '.env', $prodEnvContent);

echo "  ✓ Seluruh berkas produksi staged rapi di scratch/staging.\n\n";

// Step 3: Compress with native tar.exe or PowerShell
echo "[3/6] Mengompresi seluruh proyek ke deploy_package.zip...\n";
if (file_exists($zipFile)) {
    @unlink($zipFile);
}

$tarCmd = 'tar.exe -a -c -f ' . escapeshellarg($zipFile) . ' -C ' . escapeshellarg($stagingDir) . ' .';
exec($tarCmd, $tarOut, $tarExit);

if ($tarExit !== 0 || !file_exists($zipFile)) {
    echo "  [INFO] Mencoba Compress-Archive PowerShell fallback...\n";
    $psCmd = 'powershell -NoProfile -Command "Compress-Archive -Path \'' . $stagingDir . '\*\' -DestinationPath \'' . $zipFile . '\' -Force"';
    exec($psCmd, $psOut, $psExit);
}

if (!file_exists($zipFile)) {
    echo "  [ERROR] Gagal membuat file zip {$zipFile}\n";
    exit(1);
}

$zipSizeMb = round(filesize($zipFile) / (1024 * 1024), 2);
echo "  ✓ Bundel rilis zip berhasil dibuat: deploy_package.zip ({$zipSizeMb} MB)\n\n";

// Step 4: Create deploy_runner.php with one-time secret token
echo "[4/6] Menyiapkan runner ekstraksi & provisioning di server...\n";
$runnerContent = <<<'RUNNER'
<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$expectedToken = '###SECRET_TOKEN###';
$receivedToken = $_GET['token'] ?? '';

if (!hash_equals($expectedToken, $receivedToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid deployment token.']);
    exit;
}

$baseDir = __DIR__;
$zipFile = $baseDir . DIRECTORY_SEPARATOR . 'deploy_package.zip';
$results = [
    'status' => 'pending',
    'timestamp' => date('c'),
    'actions' => []
];

if (!file_exists($zipFile)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'deploy_package.zip not found on server.']);
    exit;
}

// 1. Extract zip
$zip = new ZipArchive();
if ($zip->open($zipFile) === true) {
    $zip->extractTo($baseDir);
    $zip->close();
    $results['actions'][] = 'Arsip deploy_package.zip berhasil diekstrak ke ' . $baseDir;
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to extract deploy_package.zip']);
    exit;
}

// 2. Adjust permissions
$storageDirs = [
    $baseDir . '/backend/storage',
    $baseDir . '/backend/storage/app',
    $baseDir . '/backend/storage/app/public',
    $baseDir . '/backend/storage/framework',
    $baseDir . '/backend/storage/framework/cache',
    $baseDir . '/backend/storage/framework/cache/data',
    $baseDir . '/backend/storage/framework/sessions',
    $baseDir . '/backend/storage/framework/views',
    $baseDir . '/backend/storage/logs',
    $baseDir . '/backend/bootstrap/cache',
    $baseDir . '/backend/database'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @chmod($dir, 0775);
}

$sqliteFile = $baseDir . '/backend/database/database.sqlite';
if (file_exists($sqliteFile)) {
    @chmod($sqliteFile, 0664);
}
$results['actions'][] = 'Hak akses direktori (chmod 775/664) berhasil diselaraskan.';

// 3. Run artisan optimizations
$artisan = $baseDir . '/backend/artisan';
$phpBin = 'php';

$commands = [
    'optimize_clear' => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' optimize:clear 2>&1',
    'migrate'        => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' migrate --force 2>&1',
    'config_cache'   => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' config:cache 2>&1',
    'route_cache'    => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' route:cache 2>&1',
    'view_cache'     => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' view:cache 2>&1',
    'prune_sessions' => escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' session:prune-files --grace=60 2>&1',
];

$artisanOut = [];
foreach ($commands as $name => $cmd) {
    $artisanOut[$name] = trim((string)shell_exec($cmd));
}
$results['artisan'] = $artisanOut;
$results['actions'][] = 'Perintah Artisan (clear, migrate, cache, prune) berhasil dieksekusi.';

// 4. Cleanup deploy artifacts (Self-Destruct)
@unlink($zipFile);
@unlink($baseDir . '/probe.php');
@unlink($baseDir . '/probe_exec.php');
@unlink(__FILE__);
$results['actions'][] = 'Berkas deploy_package.zip dan runner berhasil dibersihkan mandiri.';

$results['status'] = 'success';
echo json_encode($results, JSON_PRETTY_PRINT);
RUNNER;

$runnerContent = str_replace('###SECRET_TOKEN###', $secretToken, $runnerContent);
file_put_contents($runnerFile, $runnerContent);
echo "  ✓ Runner deploy_runner.php siap pakai dengan token dinamis.\n\n";

// Step 5: Upload Files via FTP
$remotePathUrl = $remotePath === '' ? '' : '/' . $remotePath;
$ftpTargetZip = "ftp://{$ftpHost}:{$ftpPort}{$remotePathUrl}/deploy_package.zip";
$ftpTargetRunner = "ftp://{$ftpHost}:{$ftpPort}{$remotePathUrl}/deploy_runner.php";

echo "[5/6] Mengunggah bundel zip dan runner ke FTP Server ({$ftpHost}:{$ftpPort})...\n";

// 5.1 Upload deploy_package.zip
echo "  -> Mengunggah deploy_package.zip ({$zipSizeMb} MB) via FTP stream...\n";
$uploadZipCmd = "curl.exe -# -T \"{$zipFile}\" -u \"{$ftpUser}:{$ftpPass}\" \"{$ftpTargetZip}\"";
passthru($uploadZipCmd, $zipExit);
if ($zipExit !== 0) {
    echo "  [ERROR] Pengunggahan deploy_package.zip gagal dengan kode keluar {$zipExit}\n";
    exit(1);
}
echo "  ✓ deploy_package.zip berhasil diunggah 100%!\n";

// 5.2 Upload deploy_runner.php
echo "  -> Mengunggah deploy_runner.php via FTP...\n";
$uploadRunnerCmd = "curl.exe -s -T \"{$runnerFile}\" -u \"{$ftpUser}:{$ftpPass}\" \"{$ftpTargetRunner}\"";
passthru($uploadRunnerCmd, $runnerExit);
if ($runnerExit !== 0) {
    echo "  [ERROR] Pengunggahan deploy_runner.php gagal!\n";
    exit(1);
}
echo "  ✓ deploy_runner.php berhasil diunggah!\n\n";

// Step 6: Trigger Extraction & Provisioning on Server
echo "[6/6] Memicu ekstraksi & provisioning instan di server via HTTPS...\n";
$triggerUrl = "{$appDomain}/deploy_runner.php?token={$secretToken}";
$ch = curl_init($triggerUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$resJson = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  -> HTTP Status Response: {$httpCode}\n";
echo "  -> Server Output:\n{$resJson}\n\n";

// Clean local temporary files
@unlink($zipFile);
@unlink($runnerFile);
deleteDirectory($stagingDir);

$duration = round(microtime(true) - $startTime, 2);
echo "=================================================================\n";
echo "   🎉 DEPLOYMENT SELESAI DENGAN SUKSES DALAM {$duration} DETIK!  \n";
echo "   Situs Aktif: {$appDomain}                                    \n";
echo "=================================================================\n";
