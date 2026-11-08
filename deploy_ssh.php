<?php
/**
 * HYPERION VAULT — Enterprise SSH Automated Deployment Runner
 * Cross-platform CLI orchestrator for executing remote SSH deployment.
 *
 * Mendukung 2 Model Konfigurasi:
 *   1. Single Link & 1 Password Universal (Paling Cepat & Ringkas):
 *      DEPLOY_SSH_URL="ssh://u1234567@46.202.138.212:65002"
 *      DEPLOY_PASSWORD="YourSecretPassword123!"
 *   2. Pemisahan Semua Variabel (Granular & Eksplisit):
 *      DEPLOY_SSH_HOST, DEPLOY_SSH_PORT, DEPLOY_SSH_USER, DEPLOY_SSH_PASS, DEPLOY_SSH_KEY
 *
 * Fitur Lengkap & Otomasi:
 *   - Pemisahan folder fisik: Backend (~/apps/.../backend) & Frontend (~/public_html)
 *   - Konfigurasi .env otomatis: APP_URL, FRONTEND_URL, Sanctum, CORS, Session
 *   - Penataan Symbolic Link otomatis: [public]/storage -> [backend]/storage/app/public
 *   - Konfigurasi Akses Frontend: Hardened index.php Gateway & .htaccess SPA routing
 *
 * Penggunaan CLI:
 *   php deploy_ssh.php
 *   php deploy_ssh.php --url="ssh://user@host:port" --pass="secret"
 *   php deploy_ssh.php --host=server.example.com --user=deploy --mode=split
 *   php deploy_ssh.php --subdomain=app --skip-build
 *   php deploy_ssh.php --test-mysql
 *   php deploy_ssh.php --tunnel=3307
 *
 * Konfigurasi dimuat otomatis dari: .env.deploy, backend/.env, atau .env root.
 */

declare(strict_types=1);

$startTime = microtime(true);

echo "=================================================================\n";
echo "   🛡️ HYPERION VAULT — HIGH-SPEED SSH DEPLOYMENT RUNNER        \n";
echo "=================================================================\n\n";

$baseDir = __DIR__;
$scriptSh = $baseDir . DIRECTORY_SEPARATOR . 'deploy_ssh.sh';

if (!file_exists($scriptSh)) {
    echo "  [ERROR] Berkas deploy_ssh.sh tidak ditemukan di {$scriptSh}!\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// 1. Load Konfigurasi Lingkungan (.env.deploy, backend/.env, .env)
// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
// 2. Parse CLI Options
// -----------------------------------------------------------------------------
$options = getopt('', [
    'url:', 'ssh-url:', 'ssh-cmd:', 'ssh:', 'link:',
    'host:', 'port:', 'user:', 'key:', 'pass:', 'password:',
    'mode:', 'backend-path:', 'public-path:', 'subdomain:', 'skip-build',
    'db-conn:', 'db-host:', 'db-port:', 'db-name:', 'db-user:', 'db-pass:', 'db-url:',
    'test-mysql', 'seed', 'tunnel::', 'help'
]);

if (isset($options['help'])) {
    echo "Panduan Penggunaan SSH Deployment Runner:\n";
    echo "  php deploy_ssh.php [OPSI]\n\n";
    echo "Model 1: Single Link & 1 Password Universal:\n";
    echo "  --url=\"ssh://user@host:port\"      URL atau format koneksi SSH\n";
    echo "  --pass=\"secret\"                   Password universal (SSH & MySQL)\n\n";
    echo "Model 2: Pemisahan Semua Variabel:\n";
    echo "  --host=host.domain.com          Host atau IP remote server\n";
    echo "  --port=22                       Port SSH (default: 22 atau 65002)\n";
    echo "  --user=deploy                   Username SSH\n";
    echo "  --key=~/.ssh/id_rsa             Path private key SSH lokal\n";
    echo "  --pass=secret                   Password SSH\n\n";
    echo "Opsi Arsitektur & Folder:\n";
    echo "  --mode=split|standard           Mode isolasi backend di luar webroot (default: split)\n";
    echo "  --subdomain=nama                Target subdomain (misal: 'app', 'portal')\n";
    echo "  --public-path=/path             Path folder publik webroot eksplisit\n";
    echo "  --backend-path=/path            Path folder backend eksplisit\n";
    echo "  --skip-build                    Lewati kompilasi npm build lokal\n\n";
    echo "Opsi Basis Data (Injeksi Remote):\n";
    echo "  --db-conn=mysql|sqlite          Driver database (default: mysql)\n";
    echo "  --db-host=127.0.0.1             Host MySQL di remote server\n";
    echo "  --db-port=3306                  Port MySQL di remote server\n";
    echo "  --db-name=nama_db               Nama database remote\n";
    echo "  --db-user=user_db               Username database remote\n";
    echo "  --db-pass=pass_db               Password database remote\n";
    echo "  --test-mysql                    Uji konektivitas & autentikasi MySQL saja\n";
    echo "  --tunnel[=3307]                 Buka port forwarding aman ke MySQL remote\n";
    echo "  --seed                          Jalankan database seeders setelah migrasi\n";
    echo "  --help                          Tampilkan panduan ini\n";
    exit(0);
}

$skipBuild = isset($options['skip-build']);
$testMysqlOnly = isset($options['test-mysql']);
$runSeed = isset($options['seed']);
$hasTunnel = array_key_exists('tunnel', $options);
$localTunnelPort = !empty($options['tunnel']) ? (int)$options['tunnel'] : 3307;

// -----------------------------------------------------------------------------
// 3. Helper Parser: Ekstraksi Koneksi SSH dari Single Link / Connection String
// -----------------------------------------------------------------------------
function parseSshConnectionString(string $input): array
{
    $result = [];
    $input = trim($input);
    if ($input === '') return $result;

    // Format A: URI Standard (ssh://user:pass@host:port atau ssh://user@host:port)
    if (str_starts_with($input, 'ssh://')) {
        $parts = parse_url($input);
        if ($parts !== false) {
            if (!empty($parts['host'])) $result['host'] = $parts['host'];
            if (!empty($parts['port'])) $result['port'] = (int)$parts['port'];
            if (!empty($parts['user'])) $result['user'] = $parts['user'];
            if (!empty($parts['pass'])) $result['pass'] = $parts['pass'];
            return $result;
        }
    }

    // Format B: SSH CLI Command string (e.g. 'ssh u1234567@46.202.138.212 -p 65002')
    if (str_starts_with($input, 'ssh ') || preg_match('/\s+-p\s+\d+/', $input)) {
        if (preg_match('/-p\s*(\d+)/i', $input, $mPort)) {
            $result['port'] = (int)$mPort[1];
        }
        if (preg_match('/(?:^|\s+)([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)/', $input, $mTarget)) {
            $result['user'] = $mTarget[1];
            $result['host'] = $mTarget[2];
        }
        if (!empty($result['host'])) {
            return $result;
        }
    }

    // Format C: Raw user@host:port atau user@host
    if (preg_match('/^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)(?::(\d+))?$/', $input, $mRaw)) {
        $result['user'] = $mRaw[1];
        $result['host'] = $mRaw[2];
        if (!empty($mRaw[3])) {
            $result['port'] = (int)$mRaw[3];
        }
        return $result;
    }

    return $result;
}

// Helper Parser: Ekstraksi Koneksi Database dari URL (mysql://user:pass@host:port/dbname)
function parseDbConnectionString(string $input): array
{
    $result = [];
    $input = trim($input);
    if ($input === '') return $result;

    if (str_contains($input, '://')) {
        $parts = parse_url($input);
        if ($parts !== false) {
            if (!empty($parts['scheme'])) $result['conn'] = $parts['scheme'];
            if (!empty($parts['host'])) $result['host'] = $parts['host'];
            if (!empty($parts['port'])) $result['port'] = (int)$parts['port'];
            if (!empty($parts['user'])) $result['user'] = $parts['user'];
            if (!empty($parts['pass'])) $result['pass'] = $parts['pass'];
            if (!empty($parts['path'])) $result['name'] = ltrim($parts['path'], '/');
            return $result;
        }
    }
    return $result;
}

// -----------------------------------------------------------------------------
// 4. Resolusi Parameter SSH (Kombinasi Single Link & Pemisahan Variabel)
// -----------------------------------------------------------------------------
$rawSshUrl = $options['url']
    ?? $options['ssh-url']
    ?? $options['ssh-cmd']
    ?? $options['ssh']
    ?? $options['link']
    ?? $deployEnv['DEPLOY_SSH_URL']
    ?? $deployEnv['DEPLOY_SSH_LINK']
    ?? $deployEnv['DEPLOY_SSH_COMMAND']
    ?? getenv('DEPLOY_SSH_URL')
    ?: '';

$parsedUrl = parseSshConnectionString($rawSshUrl);

// Resolusi 1 Password Universal (SSH & Database)
$universalPassword = $options['password']
    ?? $options['pass']
    ?? $deployEnv['DEPLOY_PASSWORD']
    ?? getenv('DEPLOY_PASSWORD')
    ?: '';

// SSH Host, Port, User
$sshHost = $options['host']
    ?? $parsedUrl['host']
    ?? $deployEnv['DEPLOY_SSH_HOST']
    ?? getenv('DEPLOY_SSH_HOST')
    ?: '';

$sshPortRaw = $options['port']
    ?? $parsedUrl['port']
    ?? $deployEnv['DEPLOY_SSH_PORT']
    ?? getenv('DEPLOY_SSH_PORT')
    ?: 22;
$sshPort = (int)$sshPortRaw;

$sshUser = $options['user']
    ?? $parsedUrl['user']
    ?? $deployEnv['DEPLOY_SSH_USER']
    ?? getenv('DEPLOY_SSH_USER')
    ?: '';

// SSH Password & Key
$sshPass = $options['pass']
    ?? $parsedUrl['pass']
    ?? $deployEnv['DEPLOY_SSH_PASS']
    ?? getenv('DEPLOY_SSH_PASS')
    ?: $universalPassword;

$sshKey = $options['key']
    ?? $deployEnv['DEPLOY_SSH_KEY']
    ?? getenv('DEPLOY_SSH_KEY')
    ?: '';

// Target Path & Mode
$deployMode = $options['mode'] ?? $deployEnv['DEPLOY_SSH_MODE'] ?? getenv('DEPLOY_SSH_MODE') ?: 'split';
$appName = $deployEnv['DEPLOY_APP_NAME'] ?? getenv('DEPLOY_APP_NAME') ?: basename($baseDir);
$appDomain = $deployEnv['DEPLOY_APP_DOMAIN'] ?? getenv('DEPLOY_APP_DOMAIN') ?: '';
$subdomain = $options['subdomain'] ?? $deployEnv['DEPLOY_SUBDOMAIN'] ?? getenv('DEPLOY_SUBDOMAIN') ?: '';
$publicPath = $options['public-path'] ?? $deployEnv['DEPLOY_SSH_PUBLIC_PATH'] ?? $deployEnv['DEPLOY_PUBLIC_PATH'] ?? getenv('DEPLOY_SSH_PUBLIC_PATH') ?: '';
$backendPath = $options['backend-path'] ?? $deployEnv['DEPLOY_SSH_BACKEND_PATH'] ?? $deployEnv['DEPLOY_BACKEND_PATH'] ?? getenv('DEPLOY_SSH_BACKEND_PATH') ?: '';

// -----------------------------------------------------------------------------
// 5. Resolusi Parameter Basis Data (MySQL & SQLite)
// -----------------------------------------------------------------------------
$rawDbUrl = $options['db-url'] ?? $deployEnv['DEPLOY_DB_URL'] ?? getenv('DEPLOY_DB_URL') ?: '';
$parsedDbUrl = parseDbConnectionString($rawDbUrl);

$dbConn = $options['db-conn'] ?? $parsedDbUrl['conn'] ?? $deployEnv['DEPLOY_DB_CONNECTION'] ?? getenv('DEPLOY_DB_CONNECTION') ?: 'mysql';
$dbHost = $options['db-host'] ?? $parsedDbUrl['host'] ?? $deployEnv['DEPLOY_DB_HOST'] ?? getenv('DEPLOY_DB_HOST') ?: '';
if (strtolower($dbConn) === 'mysql' && empty($dbHost)) {
    $dbHost = '127.0.0.1';
}
$dbPort = (int)($options['db-port'] ?? $parsedDbUrl['port'] ?? $deployEnv['DEPLOY_DB_PORT'] ?? getenv('DEPLOY_DB_PORT') ?: 3306);
$dbName = $options['db-name'] ?? $parsedDbUrl['name'] ?? $deployEnv['DEPLOY_DB_DATABASE'] ?? getenv('DEPLOY_DB_DATABASE') ?: '';
$dbUser = $options['db-user'] ?? $parsedDbUrl['user'] ?? $deployEnv['DEPLOY_DB_USERNAME'] ?? getenv('DEPLOY_DB_USERNAME') ?: '';
$dbPass = $options['db-pass'] ?? $parsedDbUrl['pass'] ?? $deployEnv['DEPLOY_DB_PASSWORD'] ?? getenv('DEPLOY_DB_PASSWORD') ?: $universalPassword;

// Expand SSH Key Path (~/) pada Windows & Unix
$userHome = getenv('HOME') ?: getenv('USERPROFILE') ?: ($_SERVER['HOME'] ?? ($_SERVER['USERPROFILE'] ?? ''));
if (!empty($sshKey) && str_starts_with($sshKey, '~/') && !empty($userHome)) {
    $sshKey = rtrim($userHome, '/\\') . DIRECTORY_SEPARATOR . substr($sshKey, 2);
}

// Auto-detect default SSH key if key not specified and password empty
if (empty($sshKey) && empty($sshPass) && !empty($userHome)) {
    $candidateKeys = [
        $userHome . DIRECTORY_SEPARATOR . '.ssh' . DIRECTORY_SEPARATOR . 'id_ed25519',
        $userHome . DIRECTORY_SEPARATOR . '.ssh' . DIRECTORY_SEPARATOR . 'id_rsa',
    ];
    foreach ($candidateKeys as $ck) {
        if (file_exists($ck)) {
            $sshKey = $ck;
            break;
        }
    }
}

// Auto-fallback Port Hostinger: jika port 22 dan host cocok dengan IP Hostinger
if ($sshPort === 22 && ($sshHost === '46.202.138.212' || str_contains($sshHost, 'hostinger'))) {
    $sshPort = 65002;
}

// Validasi Kelengkapan Kredensial SSH
if (empty($sshHost) || empty($sshUser)) {
    echo "  [ERROR] Parameter Host atau User SSH belum ditentukan!\n";
    echo "  Silakan tentukan melalui salah satu mekanisme berikut:\n\n";
    echo "  CARA 1: SINGLE LINK & 1 PASSWORD (Paling Cepat & Direkomendasikan):\n";
    echo "     DEPLOY_SSH_URL=\"ssh://u1234567@46.202.138.212:65002\"\n";
    echo "     DEPLOY_PASSWORD=\"YourSecretPassword123!\"\n";
    echo "     DEPLOY_DB_DATABASE=\"u1234567_hyperion\"\n";
    echo "     DEPLOY_DB_USERNAME=\"u1234567_deploy\"\n\n";
    echo "  CARA 2: PEMISAHAN SEMUA VARIABEL (.env.deploy):\n";
    echo "     DEPLOY_SSH_HOST=\"46.202.138.212\"\n";
    echo "     DEPLOY_SSH_PORT=65002\n";
    echo "     DEPLOY_SSH_USER=\"u1234567\"\n";
    echo "     DEPLOY_SSH_PASS=\"YourSecretPassword123!\"\n";
    echo "     DEPLOY_SSH_KEY=\"~/.ssh/id_rsa\"\n\n";
    echo "  CARA 3: ARGUMEN BARIS PERINTAH (CLI):\n";
    echo "     php deploy_ssh.php --url=\"ssh://u1234567@46.202.138.212:65002\" --pass=\"secret\"\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// 6. Deteksi Otomasi Password via sshpass (Jika Tersedia)
// -----------------------------------------------------------------------------
$sshpassBin = '';
$hasSshpass = false;
if (!empty($sshPass)) {
    $checkCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where.exe sshpass 2>nul' : 'which sshpass 2>/dev/null';
    $chkOut = @exec($checkCmd, $chkLines, $chkCode);
    if ($chkCode === 0 && !empty($chkOut)) {
        $hasSshpass = true;
        $sshpassBin = trim($chkOut);
    }
}

// -----------------------------------------------------------------------------
// 7. Mekanisme SSH Tunneling Langsung ke MySQL Remote (--tunnel)
// -----------------------------------------------------------------------------
if ($hasTunnel) {
    echo "=================================================================\n";
    echo "   🔗 HYPERION VAULT — SECURE SSH TUNNEL (MYSQL FORWARDING)      \n";
    echo "=================================================================\n\n";
    $keyArg = '';
    if (!empty($sshKey) && file_exists($sshKey)) {
        $keyArg = '-i ' . escapeshellarg($sshKey) . ' ';
    }
    $targetRemoteHost = !empty($dbHost) ? $dbHost : '127.0.0.1';
    $targetRemotePort = !empty($dbPort) ? $dbPort : 3306;

    $tunnelPrefix = ($hasSshpass) ? escapeshellcmd($sshpassBin) . " -p " . escapeshellarg($sshPass) . " " : '';
    $tunnelCmd = "{$tunnelPrefix}ssh -N -L {$localTunnelPort}:{$targetRemoteHost}:{$targetRemotePort} -p {$sshPort} {$keyArg}" . escapeshellarg("{$sshUser}@{$sshHost}");

    echo "Informasi Koneksi Database via SSH Tunnel:\n";
    echo "  -> Port Lokal      : 127.0.0.1:{$localTunnelPort}\n";
    echo "  -> Remote Database : {$targetRemoteHost}:{$targetRemotePort}\n";
    echo "  -> Database Name   : " . ($dbName ?: '(sesuai server)') . "\n";
    echo "  -> Database User   : " . ($dbUser ?: '(sesuai server)') . "\n\n";
    echo "Panduan Penggunaan GUI (DBeaver / TablePlus / HeidiSQL / Navicat):\n";
    echo "  • Host     : 127.0.0.1\n";
    echo "  • Port     : {$localTunnelPort}\n";
    echo "  • Database : " . ($dbName ?: 'hyperion_vault') . "\n";
    echo "  • User     : " . ($dbUser ?: 'root') . "\n";
    echo "  • Password : " . ($dbPass ? '********' : '(password Anda)') . "\n\n";
    echo "Membuka tunnel SSH aman di latar depan (Tekan Ctrl+C untuk berhenti):\n";
    echo "Command: {$tunnelCmd}\n\n";
    passthru($tunnelCmd);
    exit(0);
}

// -----------------------------------------------------------------------------
// 8. Tampilkan Ringkasan Parameter Deployment & Konfigurasi Arsitektur
// -----------------------------------------------------------------------------
echo "Parameter Server & Autentikasi:\n";
echo "  -> Host:Port   : {$sshHost}:{$sshPort}\n";
echo "  -> User        : {$sshUser}\n";
if (!empty($sshKey) && file_exists($sshKey)) {
    echo "  -> Private Key : {$sshKey}\n";
}
if (!empty($sshPass)) {
    echo "  -> Password    : ******** (" . ($hasSshpass ? "Otomatis via sshpass" : "Tersedia") . ")\n";
}

echo "\nArsitektur Pemisahan Folder (Backend vs Frontend):\n";
echo "  -> Mode Arsitektur : " . strtoupper($deployMode) . ($deployMode === 'split' ? " (OWASP Hardened: Backend di Luar Public Web Root)" : " (Standard Single Directory)") . "\n";
echo "  -> Backend Path    : " . (!empty($backendPath) ? $backendPath : "~/apps/{$appName}/backend (Terisolasi Aman)") . "\n";
echo "  -> Public Path     : " . (!empty($publicPath) ? $publicPath : (!empty($subdomain) ? "~/public_html/{$subdomain}" : "~/public_html (Aset Statis & Gateway)")) . "\n";
if (!empty($subdomain)) {
    echo "  -> Target Subdomain: {$subdomain}\n";
}
echo "  -> Symbolic Link   : [public_path]/storage -> [backend_path]/storage/app/public\n";
echo "  -> Gateway Akses   : Universal index.php + .htaccess (SPA Fallback, API & Sanctum CSRF Proxy)\n";

if (!empty($dbConn)) {
    echo "\nParameter Basis Data Target (Injeksi Remote):\n";
    echo "  -> Driver          : " . strtoupper($dbConn) . "\n";
    if (strtolower($dbConn) === 'mysql') {
        echo "  -> Host:Port       : {$dbHost}:{$dbPort}\n";
        echo "  -> Database        : {$dbName}\n";
        echo "  -> User            : {$dbUser}\n";
        echo "  -> Password        : " . (!empty($dbPass) ? '******** (Tersedia)' : '(Kosong/Tidak Ada)') . "\n";
    } elseif (strtolower($dbConn) === 'sqlite') {
        echo "  -> Database        : {$dbName}\n";
    }
}
if ($testMysqlOnly) {
    echo "  [MODE] --test-mysql aktif: Hanya memvalidasi koneksi MySQL remote.\n";
}
if ($runSeed) {
    echo "  [MODE] --seed aktif: Menjalankan database seeders setelah migrasi.\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// 9. Langkah 1: Verifikasi & Kompilasi Frontend Lokal (Fast-Path)
// -----------------------------------------------------------------------------
$frontendDir = $baseDir . DIRECTORY_SEPARATOR . 'frontend';
$buildDir = $frontendDir . DIRECTORY_SEPARATOR . 'build';

echo "[1/4] Menyiapkan status build frontend lokal...\n";
if (!$skipBuild) {
    echo "  -> Menjalankan kompilasi segar frontend (npm run build)...\n";
    $npmCmd = 'npm --prefix ' . escapeshellarg($frontendDir) . ' run build';
    passthru($npmCmd, $npmExit);
    if ($npmExit !== 0 || !file_exists($buildDir . DIRECTORY_SEPARATOR . 'index.html')) {
        echo "  [ERROR] Kompilasi frontend lokal gagal! Periksa error di atas.\n";
        exit(1);
    }
    echo "  ✓ Fresh frontend build terverifikasi.\n\n";
} else {
    echo "  [INFO] Opsi --skip-build terdeteksi. Memverifikasi build frontend yang ada...\n";
    if (!file_exists($buildDir . DIRECTORY_SEPARATOR . 'index.html')) {
        echo "  [ERROR] Direktori frontend/build belum ada! Jalankan tanpa --skip-build.\n";
        exit(1);
    }
    echo "  ✓ Build frontend terverifikasi.\n\n";
}

// Persiapan Argumen Kunci SSH & Awalan sshpass
$keyArg = '';
$scpKeyArg = '';
if (!empty($sshKey) && file_exists($sshKey)) {
    $keyArg = '-i ' . escapeshellarg($sshKey) . ' ';
    $scpKeyArg = '-i ' . escapeshellarg($sshKey) . ' ';
}

$sshpassPrefix = ($hasSshpass) ? escapeshellcmd($sshpassBin) . " -p " . escapeshellarg($sshPass) . " " : '';

// -----------------------------------------------------------------------------
// 10. Langkah 2 & 3: Pengemasan Cepat (Lean Tar) & Unggah Cepat (SCP -C)
// -----------------------------------------------------------------------------
if (!$testMysqlOnly) {
    echo "[2/4] Mengemas bundel deployment berkecepatan tinggi (Lean Payload)...\n";
    $payloadTar = $baseDir . DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR . 'deploy_payload.tar.gz';
    if (!is_dir(dirname($payloadTar))) {
        mkdir(dirname($payloadTar), 0777, true);
    }
    @unlink($payloadTar);

    // Mengemas HANYA yang dibutuhkan produksi (Anti-Bloat: membuang tests, vendor dev, logs, sessions, backups)
    $tarCmd = "tar -czf " . escapeshellarg($payloadTar) . " "
        . "--exclude=\"backend/vendor\" "
        . "--exclude=\"backend/node_modules\" "
        . "--exclude=\"backend/tests\" "
        . "--exclude=\"backend/.env\" "
        . "--exclude=\"backend/bootstrap/cache/*\" "
        . "--exclude=\"backend/storage/logs/*\" "
        . "--exclude=\"backend/storage/framework/cache/*\" "
        . "--exclude=\"backend/storage/framework/sessions/*\" "
        . "--exclude=\"backend/storage/framework/views/*\" "
        . "--exclude=\"backend/storage/app/backups/*\" "
        . "--exclude=\"backend/storage/app/public/*\" "
        . "--exclude=\"backend/storage/debugbar/*\" "
        . "--exclude=\"*.sqlite\" "
        . "--exclude=\".phpunit*\" "
        . "--exclude=\".git*\" "
        . "backend frontend/build .htaccess llms.txt";

    passthru($tarCmd, $tarExit);
    if ($tarExit !== 0 || !file_exists($payloadTar)) {
        echo "  [ERROR] Gagal mengemas bundel deployment lokal via tar!\n";
        exit(1);
    }
    $payloadSizeMb = round(filesize($payloadTar) / 1024 / 1024, 2);
    echo "  ✓ Bundel payload ramping berhasil dikemas ({$payloadSizeMb} MB).\n\n";

    echo "[3/4] Mengunggah payload ke server remote via SCP terkompresi (-C)...\n";
    $remoteScpTarget = escapeshellarg("{$sshUser}@{$sshHost}:~/deploy_payload.tar.gz");
    $scpCmd = "{$sshpassPrefix}scp -C -P {$sshPort} {$scpKeyArg}-o StrictHostKeyChecking=accept-new -o Compression=yes " . escapeshellarg($payloadTar) . " {$remoteScpTarget}";
    passthru($scpCmd, $scpExit);
    @unlink($payloadTar);
    if ($scpExit !== 0) {
        echo "  [ERROR] Gagal mengunggah payload pembaruan via SCP!\n";
        exit(1);
    }
    echo "  ✓ Payload pembaruan berhasil diunggah ke server.\n\n";
} else {
    echo "[2/4] Melewati pengemasan payload (Mode --test-mysql aktif).\n\n";
    echo "[3/4] Melewati pengunggahan SCP (Mode --test-mysql aktif).\n\n";
}

// -----------------------------------------------------------------------------
// 11. Langkah 4: Konstruksi Argumen Lingkungan & Orkestrasi Remote
// -----------------------------------------------------------------------------
echo "[4/4] Mempersiapkan sesi eksekusi remote di server...\n";

$envPassArgs = "DEPLOY_MODE=" . escapeshellarg($deployMode) . " APP_NAME=" . escapeshellarg($appName);
if (!empty($appDomain)) {
    $envPassArgs .= " DEPLOY_APP_DOMAIN=" . escapeshellarg($appDomain);
}
if (!empty($subdomain)) {
    $envPassArgs .= " DEPLOY_SUBDOMAIN=" . escapeshellarg($subdomain);
}
if (!empty($publicPath)) {
    $envPassArgs .= " CUSTOM_PUBLIC_PATH=" . escapeshellarg($publicPath);
}
if (!empty($backendPath)) {
    $envPassArgs .= " CUSTOM_BACKEND_PATH=" . escapeshellarg($backendPath);
}
if (!empty($dbConn)) {
    $envPassArgs .= " DEPLOY_DB_CONNECTION=" . escapeshellarg($dbConn);
}
if (!empty($dbHost)) {
    $envPassArgs .= " DEPLOY_DB_HOST=" . escapeshellarg($dbHost);
}
if (!empty($dbPort)) {
    $envPassArgs .= " DEPLOY_DB_PORT=" . escapeshellarg((string)$dbPort);
}
if (!empty($dbName)) {
    $envPassArgs .= " DEPLOY_DB_DATABASE=" . escapeshellarg($dbName);
}
if (!empty($dbUser)) {
    $envPassArgs .= " DEPLOY_DB_USERNAME=" . escapeshellarg($dbUser);
}
if (!empty($dbPass)) {
    $envPassArgs .= " DEPLOY_DB_PASSWORD=" . escapeshellarg($dbPass);
}
if ($testMysqlOnly) {
    $envPassArgs .= " TEST_MYSQL_ONLY=1";
}
if ($runSeed) {
    $envPassArgs .= " RUN_SEED=1";
}

// Eksekusi streaming shell script via SSH
$remoteTarget = escapeshellarg("{$sshUser}@{$sshHost}");
$sshCmd = "{$sshpassPrefix}ssh -C -p {$sshPort} {$keyArg}-o StrictHostKeyChecking=accept-new {$remoteTarget} \"{$envPassArgs} bash -s\" < " . escapeshellarg($scriptSh);

echo "  -> Menghubungi remote server via SSH ({$sshHost}:{$sshPort})...\n\n";

// Eksekusi Remote Deployment
echo "-----------------------------------------------------------------\n";
passthru($sshCmd, $exitCode);
echo "-----------------------------------------------------------------\n\n";

$duration = round(microtime(true) - $startTime, 2);

if ($exitCode === 0) {
    echo "=================================================================\n";
    echo "   🎉 DEPLOYMENT SSH SELESAI DENGAN SUKSES DALAM {$duration} DETIK! \n";
    echo "=================================================================\n";
} else {
    echo "=================================================================\n";
    echo "   ❌ DEPLOYMENT SSH GAGAL DENGAN KODE KELUAR: {$exitCode}        \n";
    echo "=================================================================\n";
    exit($exitCode);
}
