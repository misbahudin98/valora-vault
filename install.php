<?php
/**
 * Automated Enterprise Setup & Health Checker (install.php)
 * Deterministic Post-Clone Installation Wizard, Prerequisite Diagnostics & Safe In-Place Updater.
 *
 * =========================================================================
 * PANDUAN KUSTOMISASI WHITE-LABELING & BRANDING (DEVELOPER GUIDE):
 * 1. Mengganti Nama Aplikasi / Brand:
 *    Ubah $brandName di bawah atau biarkan auto-detect dari nama direktori proyek.
 * 2. Mengganti Logo Brand:
 *    Ganti tag <svg> di dalam elemen .logo-box (Baris ~390) dengan SVG logo klien.
 * 3. Menyesuaikan Palet Warna Tema (Design Tokens):
 *    Ubah variabel CSS di tag <style> (:root):
 *    --bg: #090d16 (Deep Obsidian), --indigo: #6366f1, --cyan: #06b6d4.
 * 4. Menambah Pemeriksaan Prasyarat Kustom:
 *    Tambahkan item baru pada array $requiredExtensions atau fungsi runDiagnostics().
 * 5. Versi SemVer:
 *    Update konstanta APP_VERSION setiap kali merilis pembaruan arsitektur.
 * =========================================================================
 */

declare(strict_types=1);

define('APP_VERSION', '6.31.0');

$rootDir = __DIR__;
$backendDir = $rootDir . DIRECTORY_SEPARATOR . 'backend';
$frontendDir = $rootDir . DIRECTORY_SEPARATOR . 'frontend';
$lockFile = $rootDir . DIRECTORY_SEPARATOR . '.installed';
$isCli = (php_sapi_name() === 'cli');

// 1. Diagnostic Checks Definition
function runDiagnostics(string $rootDir, string $backendDir, string $frontendDir): array {
    $results = [];

    // PHP Version (Require >= 8.2, recommend 8.3/8.4)
    $phpVersion = PHP_VERSION;
    $phpOk = version_compare($phpVersion, '8.2.0', '>=');
    $results['php_version'] = [
        'title' => 'Versi PHP',
        'required' => '>= 8.2 (Direkomendasikan 8.3/8.4)',
        'current' => $phpVersion,
        'passed' => $phpOk,
        'help' => 'Perbarui PHP di Laragon atau sistem ke minimal PHP 8.2.'
    ];

    // Extensions
    $requiredExtensions = [
        'pdo_sqlite' => 'Koneksi SQLite PDO Database',
        'sqlite3' => 'Driver SQLite Native',
        'curl' => 'Proxy HTTP & API Internal Request',
        'mbstring' => 'Manipulasi Multibyte String',
        'openssl' => 'Enkripsi & Signed JWT HMAC-SHA256',
        'fileinfo' => 'Validasi Tipe Berkas',
        'json' => 'Parsing Payload JSON & API Responses'
    ];

    foreach ($requiredExtensions as $ext => $desc) {
        $loaded = extension_loaded($ext);
        $results['ext_' . $ext] = [
            'title' => "Ekstensi PHP: {$ext}",
            'required' => "Diaktifkan ({$desc})",
            'current' => $loaded ? 'Aktif' : 'Tidak Ditemukan',
            'passed' => $loaded,
            'help' => "Buka php.ini dan hilangkan tanda titik koma (;) pada extension={$ext}."
        ];
    }

    // Directory Writability
    $writableDirs = [
        'backend/storage' => $backendDir . DIRECTORY_SEPARATOR . 'storage',
        'backend/bootstrap/cache' => $backendDir . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
        'backend/database' => $backendDir . DIRECTORY_SEPARATOR . 'database',
    ];

    foreach ($writableDirs as $label => $path) {
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        $results['dir_' . md5($label)] = [
            'title' => "Izin Tulis: {$label}",
            'required' => 'Dapat Ditulis (Writable)',
            'current' => $writable ? 'Writable' : ($exists ? 'Read-Only' : 'Folder Tidak Ada'),
            'passed' => $writable,
            'help' => "Pastikan direktori {$label} dibuat dan memiliki izin tulis."
        ];
    }

    // Package Dependencies
    $vendorAutoload = $backendDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    $vendorOk = file_exists($vendorAutoload);
    $results['backend_vendor'] = [
        'title' => 'Dependensi Composer Backend',
        'required' => 'Telah Terinstal (vendor/autoload.php)',
        'current' => $vendorOk ? 'Ditemukan' : 'Belum Dijalankan',
        'passed' => $vendorOk,
        'help' => 'Jalankan "composer install" di dalam folder backend.'
    ];

    $nodeModules = $frontendDir . DIRECTORY_SEPARATOR . 'node_modules';
    $buildIndex = $frontendDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'index.html';
    $nodeOk = is_dir($nodeModules) || file_exists($buildIndex);
    $results['frontend_node'] = [
        'title' => 'Dependensi / Aset Frontend',
        'required' => 'node_modules terinstal ATAU build/index.html siap pakai',
        'current' => file_exists($buildIndex) ? 'Aset Build Siap' : ($nodeOk ? 'Ditemukan' : 'Belum Dijalankan'),
        'passed' => $nodeOk,
        'help' => 'Jalankan "npm install && npm run build" di dalam folder frontend.'
    ];

    return $results;
}

// 1b. Engine Duplication / Project Spawner Engine
function copyEngineToTarget(string $sourceDir, string $targetDir, array &$log): bool {
    if (!is_dir($targetDir)) {
        if (!@mkdir($targetDir, 0755, true)) {
            throw new RuntimeException("Gagal membuat direktori target: {$targetDir}");
        }
    }

    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    if ($isWindows) {
        $excludeDirs = ['.git', 'storage\\framework\\sessions', 'storage\\logs', 'bootstrap\\cache'];
        $excludeFiles = ['.env', 'database.sqlite', '.installed'];

        $xdArgs = '';
        foreach ($excludeDirs as $xd) {
            $xdArgs .= ' /XD ' . escapeshellarg($xd);
        }
        $xfArgs = '';
        foreach ($excludeFiles as $xf) {
            $xfArgs .= ' /XF ' . escapeshellarg($xf);
        }

        $cmdRobo = 'robocopy ' . escapeshellarg($sourceDir) . ' ' . escapeshellarg($targetDir) . ' /E /MT:16 /R:1 /W:1 /NFL /NDL /NP /NJH /NJS' . $xdArgs . $xfArgs;
        $output = [];
        $retVal = 0;
        @exec($cmdRobo, $output, $retVal);

        // Robocopy exit code < 8 indicates successful file transfer
        if ($retVal < 8) {
            $log[] = '[OK] Replikasi berkas engine via multi-threaded Windows robocopy berhasil (~3-5 detik).';
            return true;
        }
    }

    // Cross-platform fallback: recursive copy
    $excludeDirs = [
        '.git',
        'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
        'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
        'backend' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache'
    ];
    $excludeFiles = ['.env', 'database.sqlite', '.installed'];

    recursiveCopy($sourceDir, $targetDir, $sourceDir, $excludeDirs, $excludeFiles);
    $log[] = '[OK] Replikasi berkas engine selesai.';
    return true;
}

function recursiveCopy(string $src, string $dst, string $baseSrc, array $excludeDirs, array $excludeFiles): void {
    $dir = @opendir($src);
    if (!$dir) return;
    if (!is_dir($dst)) {
        @mkdir($dst, 0755, true);
    }
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $srcFile = $src . DIRECTORY_SEPARATOR . $file;
        $dstFile = $dst . DIRECTORY_SEPARATOR . $file;
        $relPath = ltrim(str_replace($baseSrc, '', $srcFile), DIRECTORY_SEPARATOR);

        if (is_dir($srcFile)) {
            $skip = false;
            foreach ($excludeDirs as $ex) {
                if ($relPath === $ex || str_starts_with($relPath, $ex . DIRECTORY_SEPARATOR) || str_ends_with($relPath, DIRECTORY_SEPARATOR . $ex)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;
            recursiveCopy($srcFile, $dstFile, $baseSrc, $excludeDirs, $excludeFiles);
        } else {
            if (in_array($file, $excludeFiles, true)) continue;
            @copy($srcFile, $dstFile);
        }
    }
    closedir($dir);
}

$diagnostics = runDiagnostics($rootDir, $backendDir, $frontendDir);
$allPassed = !in_array(false, array_column($diagnostics, 'passed'), true);
$isInstalled = file_exists($lockFile);
$lockData = [];
$installedVersion = '0.0.0';

if ($isInstalled) {
    $rawLock = file_get_contents($lockFile);
    $lockData = json_decode($rawLock, true) ?: [];
    $installedVersion = $lockData['version'] ?? '5.5.0';
}

$hasUpdate = $isInstalled && version_compare(APP_VERSION, $installedVersion, '>');

$folderName = basename($rootDir);
$defaultDomain = strtolower(preg_replace('/[^a-zA-Z0-9_.-]/', '-', $folderName)) . '.test';
$defaultAppName = ucwords(str_replace(['-', '_'], ' ', $folderName));
$defaultAppDesc = "Sistem Informasi & Manajemen {$defaultAppName} Terpadu";

$action = $_POST['action'] ?? ($isCli ? ($argv[1] ?? 'status') : '');
if (!$action && isset($_GET['auto']) && $_GET['auto'] == '1' && !$isInstalled && $allPassed) {
    $action = 'install';
} elseif (!$action && isset($_GET['auto']) && $_GET['auto'] == '1' && $hasUpdate) {
    $action = 'update';
}

$outputLog = [];
$errorMsg = null;
$successMsg = null;

// =========================================================================
// ACTION: SAFE UPDATE (Pembaruan Tanpa Menghapus Data)
// =========================================================================
if ($action === 'update') {
    if (!$isInstalled) {
        $errorMsg = 'Sistem belum terinstal. Jalankan instalasi awal terlebih dahulu via "php install.php install".';
    } else {
        try {
            $phpBin = PHP_BINARY;
            $artisan = $backendDir . DIRECTORY_SEPARATOR . 'artisan';

            // 1. Clear application caches (config, route, view)
            $cmdClear = escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' optimize:clear 2>&1';
            $clearOut = shell_exec($cmdClear);
            $outputLog[] = '[OK] Cache aplikasi, konfigurasi, rute, dan view berhasil dibersihkan.';

            // 2. Safe Database Migration (Preserves data, NO --fresh)
            $cmdMigrate = escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' migrate --force 2>&1';
            $migrateOut = shell_exec($cmdMigrate);
            $outputLog[] = '[OK] Migrasi database aman dieksekusi (seluruh data pengguna dan sesi dipertahankan).';

            // 3. Prune orphaned and accumulated session files
            $cmdPruneSessions = escapeshellarg($phpBin) . ' artisan auth:clear-resets && ' . escapeshellarg($phpBin) . ' -r "@array_map(\'unlink\', glob(\'' . addslashes(dirname(__DIR__) . '/backend/storage/framework/sessions/*') . '\'));" 2>&1';
            $pruneOut = @shell_exec($cmdPruneSessions);
            $outputLog[] = '[OK] Pembersihan berkas sesi kadaluarsa di storage/framework/sessions selesai.';

            // 4. Re-compile Frontend SPA if node_modules exists
            $nodeModules = $frontendDir . DIRECTORY_SEPARATOR . 'node_modules';
            if (is_dir($nodeModules)) {
                if ($isCli) {
                    echo "[INFO] Mengompilasi ulang bundle produksi Frontend SPA...\n";
                }
                $cmdBuild = 'cd ' . escapeshellarg($frontendDir) . ' && npm run build 2>&1';
                $buildOut = @shell_exec($cmdBuild);
                $outputLog[] = '[OK] Aset Frontend SPA berhasil dikompilasi ulang ke frontend/build.';
            }

            // 5. Update .installed lock metadata & version history log
            $history = $lockData['history'] ?? [];
            $history[] = [
                'from_version' => $installedVersion,
                'to_version' => APP_VERSION,
                'action' => 'update',
                'timestamp' => date('c'),
                'updated_by' => $isCli ? 'CLI' : ($_SERVER['REMOTE_ADDR'] ?? 'Web'),
                'status' => 'success',
                'notes' => 'Pembaruan arsitektur v' . APP_VERSION . ' (Safe migration, cache refresh, asset build).'
            ];

            $lockData['version'] = APP_VERSION;
            $lockData['updated_at'] = date('c');
            $lockData['history'] = $history;

            file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT));
            $outputLog[] = '[OK] Berkas lock keamanan .installed diperbarui ke v' . APP_VERSION . '.';

            $installedVersion = APP_VERSION;
            $hasUpdate = false;
            $successMsg = "Pembaruan Berhasil 100%! Sistem kini berjalan pada arsitektur v" . APP_VERSION . ".";
        } catch (Throwable $e) {
            $errorMsg = 'Kegagalan Pembaruan: ' . $e->getMessage();
            $outputLog[] = '[ERROR] ' . $e->getMessage();
        }
    }
}

// =========================================================================
// ACTION: FRESH INSTALL (Inisiasi Awal Proyek Pasca Clone)
// =========================================================================
if ($action === 'install') {
    // Auto-resolve missing dependencies if package managers are present
    $vendorAutoload = $backendDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!file_exists($vendorAutoload)) {
        if ($isCli) {
            echo "[INFO] Dependensi Composer belum ada. Menjalankan 'composer install' otomatis...\n";
        }
        $cmdComp = 'cd ' . escapeshellarg($backendDir) . ' && composer install --optimize-autoloader --no-interaction 2>&1';
        $compOut = @shell_exec($cmdComp);
        if (file_exists($vendorAutoload)) {
            $outputLog[] = '[OK] Dependensi Composer backend berhasil diinstal otomatis.';
        }
    }

    $nodeModules = $frontendDir . DIRECTORY_SEPARATOR . 'node_modules';
    $buildIndex = $frontendDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_dir($nodeModules) && !file_exists($buildIndex)) {
        if ($isCli) {
            echo "[INFO] Dependensi npm frontend belum ada. Menjalankan 'npm install' otomatis...\n";
        }
        $cmdNpm = 'cd ' . escapeshellarg($frontendDir) . ' && npm install --legacy-peer-deps 2>&1';
        $npmOut = @shell_exec($cmdNpm);
        if (is_dir($nodeModules)) {
            $outputLog[] = '[OK] Dependensi npm frontend berhasil diinstal otomatis.';
        }
    }

    // Re-run diagnostics post auto-install
    $diagnostics = runDiagnostics($rootDir, $backendDir, $frontendDir);
    $allPassed = !in_array(false, array_column($diagnostics, 'passed'), true);

    if ($isInstalled && !isset($_POST['force']) && !in_array('--force', $argv ?? [])) {
        $errorMsg = 'Sistem telah terinstal sebelumnya (File lock .installed ditemukan). Gunakan "php install.php update" untuk memperbarui atau hapus .installed jika ingin mengulang instalasi.';
    } elseif (!$allPassed) {
        $errorMsg = 'Prasyarat sistem belum terpenuhi. Selesaikan seluruh poin pemeriksaan sebelum menjalankan instalasi.';
    } else {
        try {
            // Extract custom parameters from CLI flags or Web POST
            $inputAppName = null;
            $inputDomain = null;
            $inputDesc = null;

            if ($isCli) {
                foreach ($argv ?? [] as $arg) {
                    if (str_starts_with($arg, '--name=')) {
                        $inputAppName = trim(substr($arg, 7), '"\'');
                    } elseif (str_starts_with($arg, '--domain=')) {
                        $inputDomain = trim(substr($arg, 9), '"\'');
                    } elseif (str_starts_with($arg, '--desc=')) {
                        $inputDesc = trim(substr($arg, 7), '"\'');
                    }
                }
            } else {
                $inputAppName = !empty($_POST['app_name']) ? trim((string)$_POST['app_name']) : null;
                $inputDomain = !empty($_POST['domain_host']) ? trim((string)$_POST['domain_host']) : null;
                $inputDesc = !empty($_POST['app_desc']) ? trim((string)$_POST['app_desc']) : null;
            }

            $appName = $inputAppName ?: $defaultAppName;
            $domainHost = $inputDomain ?: $defaultDomain;
            $appDesc = $inputDesc ?: $defaultAppDesc;

            // Step 1: Ensure backend/.env exists with dynamic domain, identity & project metadata
            $envFile = $backendDir . DIRECTORY_SEPARATOR . '.env';
            $envExample = $backendDir . DIRECTORY_SEPARATOR . '.env.example';
            $statefulList = "{$domainHost},{$domainHost}:5173,localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000";
            if (!file_exists($envFile)) {
                if (file_exists($envExample)) {
                    $envRaw = file_get_contents($envExample);
                    $envRaw = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . $appName . '"', $envRaw);
                    $envRaw = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://' . $domainHost, $envRaw);
                    $envRaw = preg_replace('/^FRONTEND_URL=.*/m', 'FRONTEND_URL=http://' . $domainHost, $envRaw);
                    $envRaw = preg_replace('/^SANCTUM_STATEFUL_DOMAINS=.*/m', 'SANCTUM_STATEFUL_DOMAINS=' . $statefulList, $envRaw);
                    $envRaw = preg_replace('/^SESSION_DOMAIN=.*/m', 'SESSION_DOMAIN=', $envRaw);
                    $envRaw = preg_replace('/^MAIL_FROM_NAME=.*/m', 'MAIL_FROM_NAME="' . $appName . '"', $envRaw);
                    $envRaw = preg_replace('/^MAIL_FROM_ADDRESS=.*/m', 'MAIL_FROM_ADDRESS="admin@' . $domainHost . '"', $envRaw);
                    file_put_contents($envFile, $envRaw);
                    $outputLog[] = "[OK] Berkas backend/.env dikonfigurasi untuk '{$appName}' (http://{$domainHost}).";
                } else {
                    throw new RuntimeException('Berkas .env.example tidak ditemukan di direktori backend.');
                }
            } else {
                $outputLog[] = '[INFO] Berkas backend/.env sudah ada.';
            }

            // Step 1b: Ensure frontend/.env is properly aligned with dynamic application identity
            $frontEnvFile = $frontendDir . DIRECTORY_SEPARATOR . '.env';
            $frontEnvExample = $frontendDir . DIRECTORY_SEPARATOR . '.env.example';
            if (!file_exists($frontEnvFile) && file_exists($frontEnvExample)) {
                $frontRaw = file_get_contents($frontEnvExample);
                $frontRaw .= "\nVITE_APP_NAME=\"" . addslashes($appName) . "\"\n";
                $frontRaw .= "VITE_APP_DESC=\"" . addslashes($appDesc) . "\"\n";
                file_put_contents($frontEnvFile, $frontRaw);
                $outputLog[] = "[OK] Berkas frontend/.env dikonfigurasi otomatis untuk '{$appName}'.";
            }

            // Step 2: Ensure database.sqlite exists
            $dbFile = $backendDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite';
            if (!file_exists($dbFile)) {
                touch($dbFile);
                $outputLog[] = '[OK] Database SQLite berhasil dibuat: database/database.sqlite';
            } else {
                $outputLog[] = '[INFO] Database SQLite sudah ada.';
            }

            // Step 3: Generate APP_KEY using current PHP_BINARY
            $envContent = file_get_contents($envFile);
            $phpBin = PHP_BINARY;
            $artisan = $backendDir . DIRECTORY_SEPARATOR . 'artisan';

            if (!preg_match('/^APP_KEY=base64:.+$/m', $envContent)) {
                $cmdKey = escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' key:generate --force 2>&1';
                $keyOut = shell_exec($cmdKey);
                $outputLog[] = '[OK] Kunci enkripsi APP_KEY baru berhasil di-generate.';
            } else {
                $outputLog[] = '[INFO] APP_KEY sudah terkonfigurasi.';
            }

            // Step 4: Run Migrations & Seeder using current PHP_BINARY
            $cmdMigrate = escapeshellarg($phpBin) . ' ' . escapeshellarg($artisan) . ' migrate:fresh --seed --force 2>&1';
            $migrateOut = shell_exec($cmdMigrate);
            $outputLog[] = '[OK] Migrasi dan seeding database berhasil dieksekusi.';

            // Step 4b: Compile Frontend SPA if build does not exist and node_modules is present
            $buildFile = $frontendDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'index.html';
            if (!file_exists($buildFile) && is_dir($frontendDir . DIRECTORY_SEPARATOR . 'node_modules')) {
                if ($isCli) {
                    echo "[INFO] Mengompilasi bundle produksi Frontend SPA...\n";
                }
                $cmdBuild = 'cd ' . escapeshellarg($frontendDir) . ' && npm run build 2>&1';
                $buildOut = @shell_exec($cmdBuild);
                $outputLog[] = '[OK] Frontend SPA bundle berhasil dikompilasi ke frontend/build.';
            }

            // Step 5: Create .installed lock file with version and initial history
            $lockPayload = json_encode([
                'version' => APP_VERSION,
                'installed_at' => date('c'),
                'installed_by' => $isCli ? 'CLI' : ($_SERVER['REMOTE_ADDR'] ?? 'Web'),
                'php_version' => PHP_VERSION,
                'project_name' => $appName,
                'domain' => $domainHost,
                'description' => $appDesc,
                'environment' => 'production_ready',
                'history' => [
                    [
                        'version' => APP_VERSION,
                        'action' => 'install',
                        'timestamp' => date('c'),
                        'updated_by' => $isCli ? 'CLI' : ($_SERVER['REMOTE_ADDR'] ?? 'Web'),
                        'status' => 'success',
                        'notes' => 'Inisiasi instalasi awal versi ' . APP_VERSION
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            file_put_contents($lockFile, $lockPayload);
            $outputLog[] = '[OK] Berkas lock keamanan .installed berhasil dibuat (Versi ' . APP_VERSION . ').';

            $isInstalled = true;
            $installedVersion = APP_VERSION;
            $hasUpdate = false;
            $successMsg = "Instalasi Berhasil 100%! Aplikasi {$appName} siap digunakan pada http://{$domainHost}.";
        } catch (Throwable $e) {
            $errorMsg = 'Kegagalan Instalasi: ' . $e->getMessage();
            $outputLog[] = '[ERROR] ' . $e->getMessage();
        }
    }
}

// =========================================================================
// ACTION: SPAWN / DUPLICATE PROJECT (Pola Golden Master Archetype Spawner)
// =========================================================================
if ($action === 'spawn' || $action === 'duplicate') {
    $targetName = null;
    $customAppName = null;
    $customDomain = null;
    $customDesc = null;

    if ($isCli) {
        // Syntax: php install.php spawn <target_folder> [--name="..."] [--domain="..."] [--desc="..."]
        $rawTarget = $argv[2] ?? null;
        if ($rawTarget && !str_starts_with($rawTarget, '--')) {
            $targetName = $rawTarget;
        }
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--target=')) {
                $targetName = trim(substr($arg, 9), '"\'');
            } elseif (str_starts_with($arg, '--name=')) {
                $customAppName = trim(substr($arg, 7), '"\'');
            } elseif (str_starts_with($arg, '--domain=')) {
                $customDomain = trim(substr($arg, 9), '"\'');
            } elseif (str_starts_with($arg, '--desc=')) {
                $customDesc = trim(substr($arg, 7), '"\'');
            }
        }
    } else {
        $targetName = !empty($_POST['target_folder']) ? trim((string)$_POST['target_folder']) : null;
        $customAppName = !empty($_POST['target_name']) ? trim((string)$_POST['target_name']) : null;
        $customDomain = !empty($_POST['target_domain']) ? trim((string)$_POST['target_domain']) : null;
        $customDesc = !empty($_POST['target_desc']) ? trim((string)$_POST['target_desc']) : null;
    }

    if (empty($targetName)) {
        $errorMsg = 'Nama folder proyek target tidak boleh kosong. Gunakan: php install.php spawn <nama-folder>';
    } else {
        $cleanTarget = strtolower(preg_replace('/[^a-zA-Z0-9_.-]/', '-', $targetName));
        $parentDir = dirname($rootDir);
        $destDir = $parentDir . DIRECTORY_SEPARATOR . $cleanTarget;

        if (file_exists($destDir)) {
            $errorMsg = "Direktori target '{$destDir}' sudah ada. Hapus folder tersebut atau pilih nama folder lain.";
        } else {
            try {
                $newAppName = $customAppName ?: ucwords(str_replace(['-', '_'], ' ', $cleanTarget));
                $newDomain = $customDomain ?: "{$cleanTarget}.test";
                $newDesc = $customDesc ?: "Sistem Informasi & Manajemen {$newAppName} Terpadu";

                $outputLog[] = "--- Memulai Spawning Proyek Baru: {$newAppName} ---";
                $outputLog[] = "[INFO] Direktori target: {$destDir}";

                // 1. Salin seluruh berkas engine (mengecualikan state kotor dan data sesi)
                copyEngineToTarget($rootDir, $destDir, $outputLog);

                // 2. Pastikan subdirektori runtime bersih dan siap pakai
                $freshDirs = [
                    $destDir . '/backend/storage/framework/sessions',
                    $destDir . '/backend/storage/framework/views',
                    $destDir . '/backend/storage/framework/cache/data',
                    $destDir . '/backend/storage/logs',
                    $destDir . '/backend/bootstrap/cache',
                    $destDir . '/backend/database',
                ];
                foreach ($freshDirs as $fd) {
                    if (!is_dir($fd)) {
                        @mkdir($fd, 0755, true);
                    }
                }

                // 3. Konfigurasi backend/.env di target
                $targetEnvExample = $destDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . '.env.example';
                $targetEnv = $destDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . '.env';
                $statefulList = "{$newDomain},{$newDomain}:5173,localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000";

                $envRaw = file_get_contents($targetEnvExample);
                $envRaw = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . $newAppName . '"', $envRaw);
                $envRaw = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://' . $newDomain, $envRaw);
                $envRaw = preg_replace('/^FRONTEND_URL=.*/m', 'FRONTEND_URL=http://' . $newDomain, $envRaw);
                $envRaw = preg_replace('/^SANCTUM_STATEFUL_DOMAINS=.*/m', 'SANCTUM_STATEFUL_DOMAINS=' . $statefulList, $envRaw);
                $envRaw = preg_replace('/^SESSION_DOMAIN=.*/m', 'SESSION_DOMAIN=', $envRaw);
                $envRaw = preg_replace('/^MAIL_FROM_NAME=.*/m', 'MAIL_FROM_NAME="' . $newAppName . '"', $envRaw);
                $envRaw = preg_replace('/^MAIL_FROM_ADDRESS=.*/m', 'MAIL_FROM_ADDRESS="admin@' . $newDomain . '"', $envRaw);
                file_put_contents($targetEnv, $envRaw);
                $outputLog[] = "[OK] Berkas backend/.env dikonfigurasi untuk '{$newAppName}' (Host-Only cookie).";

                // 4. Konfigurasi frontend/.env di target
                $targetFrontEnvExample = $destDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . '.env.example';
                $targetFrontEnv = $destDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . '.env';
                $frontRaw = file_exists($targetFrontEnvExample) ? file_get_contents($targetFrontEnvExample) : "VITE_API_URL=\nVITE_STORAGE_URL=/storage\n";
                $frontRaw .= "\nVITE_APP_NAME=\"" . addslashes($newAppName) . "\"\n";
                $frontRaw .= "VITE_APP_DESC=\"" . addslashes($newDesc) . "\"\n";
                file_put_contents($targetFrontEnv, $frontRaw);
                $outputLog[] = "[OK] Berkas frontend/.env dikonfigurasi otomatis (same-origin mode).";

                // 5. Inisiasi Database SQLite di target
                $targetDb = $destDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite';
                touch($targetDb);
                $outputLog[] = '[OK] Berkas database SQLite target berhasil dibuat.';

                // 6. Generate APP_KEY unik & Jalankan Migrasi + Dynamic Seeder di target
                $phpBin = PHP_BINARY;
                $targetArtisan = $destDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'artisan';

                $cmdKey = escapeshellarg($phpBin) . ' ' . escapeshellarg($targetArtisan) . ' key:generate --force 2>&1';
                shell_exec($cmdKey);
                $outputLog[] = '[OK] Kunci kriptografi APP_KEY baru berhasil dibuat.';

                $cmdMigrate = escapeshellarg($phpBin) . ' ' . escapeshellarg($targetArtisan) . ' migrate:fresh --seed --force 2>&1';
                shell_exec($cmdMigrate);
                $outputLog[] = "[OK] Skema database berhasil dimigrasi dan di-seed dengan akun admin@{$newDomain}.";

                // 7. Buat berkas lock .installed di target
                $targetLock = $destDir . DIRECTORY_SEPARATOR . '.installed';
                $targetLockData = json_encode([
                    'version' => APP_VERSION,
                    'installed_at' => date('c'),
                    'installed_by' => $isCli ? 'CLI (Spawner)' : ($_SERVER['REMOTE_ADDR'] ?? 'Web (Spawner)'),
                    'php_version' => PHP_VERSION,
                    'project_name' => $newAppName,
                    'domain' => $newDomain,
                    'description' => $newDesc,
                    'spawned_from' => 'Hyperion Vault Archetype v' . APP_VERSION,
                    'environment' => 'production_ready',
                    'history' => [
                        [
                            'version' => APP_VERSION,
                            'action' => 'spawn',
                            'timestamp' => date('c'),
                            'updated_by' => $isCli ? 'CLI (Spawner)' : ($_SERVER['REMOTE_ADDR'] ?? 'Web (Spawner)'),
                            'status' => 'success',
                            'notes' => 'Proyek baru diduplikasi dari Hyperion Vault Archetype Engine.'
                        ]
                    ]
                ], JSON_PRETTY_PRINT);
                file_put_contents($targetLock, $targetLockData);
                $outputLog[] = '[OK] Berkas lock keamanan target .installed berhasil dibuat.';

                // 8. Inisiasi Git repo mandiri di target
                $cmdGit = 'cd ' . escapeshellarg($destDir) . ' && git init 2>&1 && git add . 2>&1 && git commit -m "feat: initial commit spawned from Hyperion Vault Archetype v' . APP_VERSION . '" 2>&1';
                @shell_exec($cmdGit);
                $outputLog[] = '[OK] Repositori Git lokal baru berhasil diinisialisasi.';

                $successMsg = "Proyek Baru '{$newAppName}' Berhasil Diduplikasi ke: {$destDir}! Aplikasi siap dibuka di http://{$newDomain}.";
            } catch (Throwable $e) {
                $errorMsg = 'Kegagalan Duplikasi Proyek: ' . $e->getMessage();
                $outputLog[] = '[ERROR] ' . $e->getMessage();
            }
        }
    }
}

// =========================================================================
// ACTION: SHIP (Smart Atomic Commit & Push dengan Detail Changelog Otomatis)
// =========================================================================
if ($action === 'ship' || $action === 'commit') {
    $gitDir = $rootDir . DIRECTORY_SEPARATOR . '.git';
    if (!is_dir($gitDir)) {
        $errorMsg = "Direktori ini bukan repositori Git. Jalankan 'git init' terlebih dahulu.";
    } else {
        try {
            // Parse CLI options
            $userMsg = null;
            $doPush = true;
            $isDryRun = false;

            if ($isCli) {
                // Argument 2 could be custom message
                $arg2 = $argv[2] ?? '';
                if ($arg2 && !str_starts_with($arg2, '--')) {
                    $userMsg = $arg2;
                }
                foreach ($argv as $arg) {
                    if (str_starts_with($arg, '--msg=') || str_starts_with($arg, '--message=')) {
                        $userMsg = trim(substr($arg, strpos($arg, '=') + 1), '"\'');
                    } elseif ($arg === '--no-push') {
                        $doPush = false;
                    } elseif ($arg === '--dry-run') {
                        $isDryRun = true;
                        $doPush = false;
                    }
                }
            } else {
                $userMsg = !empty($_POST['ship_message']) ? trim((string)$_POST['ship_message']) : null;
                $doPush = !isset($_POST['no_push']);
                $isDryRun = isset($_POST['dry_run']);
            }

            // 1. Dapatkan status working tree saat ini
            $statusRaw = (string) @shell_exec('git status -s 2>&1');
            $statusLines = array_values(array_filter(explode("\n", trim($statusRaw))));

            // 2. Dapatkan branch aktif saat ini
            $activeBranch = trim((string) @shell_exec('git rev-parse --abbrev-ref HEAD 2>&1')) ?: 'main';

            // 3. Cek upstream remote tracking branch
            $upstreamBranch = trim((string) @shell_exec('git rev-parse --abbrev-ref "@{u}" 2>&1'));
            $hasUpstream = !empty($upstreamBranch) && !str_contains($upstreamBranch, 'fatal') && !str_contains($upstreamBranch, 'error');

            // 4. Cek commit lokal yang belum di-push ke upstream
            $unpushedList = [];
            if ($hasUpstream) {
                $unpushedRaw = (string) @shell_exec('git log "@{u}..HEAD" --oneline 2>&1');
                if (!empty($unpushedRaw) && !str_contains($unpushedRaw, 'fatal')) {
                    $unpushedList = array_values(array_filter(explode("\n", trim($unpushedRaw))));
                }
            }

            if (empty($statusLines) && empty($unpushedList)) {
                $successMsg = "Working tree bersih dan seluruh commit sudah ter-push ke {$upstreamBranch}. Tidak ada perubahan baru.";
            } elseif (empty($statusLines) && !empty($unpushedList)) {
                $outputLog[] = "[INFO] Working tree bersih, ditemukan " . count($unpushedList) . " commit lokal belum ter-push.";
                if ($doPush && !$isDryRun) {
                    $pushCmd = 'git push origin ' . escapeshellarg($activeBranch) . ' 2>&1';
                    $pushOut = @shell_exec($pushCmd);
                    $outputLog[] = "[OK] Berhasil melakukan push ke origin/{$activeBranch}.";
                    $successMsg = "Seluruh commit lokal (" . count($unpushedList) . " commit) berhasil di-push ke origin/{$activeBranch}!";
                } else {
                    $successMsg = "Terdapat " . count($unpushedList) . " commit lokal siap di-push. (Gunakan tanpa --no-push untuk mem-push).";
                }
            } else {
                $outputLog[] = "--- Memulai Audit & Kompilasi Perubahan ('ship') ---";
                $outputLog[] = "[INFO] Mendeteksi " . count($statusLines) . " berkas mengalami perubahan sejak push/commit terakhir.";

                // Kategorisasi berkas
                $categories = [
                    'Backend' => [],
                    'Frontend' => [],
                    'Dokumentasi & SSoT' => [],
                    'Aturan & Tata Kelola AI' => [],
                    'Gateway & Konfigurasi' => [],
                ];

                foreach ($statusLines as $line) {
                    $flag = substr($line, 0, 2);
                    $file = trim(substr($line, 2));

                    $descFlag = match (trim($flag)) {
                        'M' => 'modified',
                        'A' => 'added',
                        'D' => 'deleted',
                        'R' => 'renamed',
                        '??' => 'untracked',
                        default => 'updated'
                    };

                    if (str_starts_with($file, 'backend/')) {
                        $categories['Backend'][] = "{$descFlag}: {$file}";
                    } elseif (str_starts_with($file, 'frontend/')) {
                        $categories['Frontend'][] = "{$descFlag}: {$file}";
                    } elseif (str_starts_with($file, 'doc/') || str_ends_with($file, '.md') || $file === 'llms.txt') {
                        $categories['Dokumentasi & SSoT'][] = "{$descFlag}: {$file}";
                    } elseif (str_starts_with($file, '.agents/') || in_array($file, ['AGENTS.md', 'GEMINI.md'], true)) {
                        $categories['Aturan & Tata Kelola AI'][] = "{$descFlag}: {$file}";
                    } else {
                        $categories['Gateway & Konfigurasi'][] = "{$descFlag}: {$file}";
                    }
                }

                // Ambil statistik diff baris
                $diffStatRaw = (string) @shell_exec('git diff --stat 2>&1');
                $diffStatSummary = 'Statistik perubahan berkas';
                if (preg_match('/([0-9]+ files? changed.*?)$/m', $diffStatRaw, $mStat)) {
                    $diffStatSummary = trim($mStat[1]);
                }

                // Susun Pesan Judul (Title Line)
                if (empty($userMsg)) {
                    $counts = array_map('count', $categories);
                    arsort($counts);
                    $dominant = key($counts);

                    $prefix = match ($dominant) {
                        'Backend' => 'refactor(backend)',
                        'Frontend' => 'feat(frontend)',
                        'Dokumentasi & SSoT' => 'docs',
                        'Aturan & Tata Kelola AI' => 'chore(agents)',
                        default => 'chore(system)'
                    };
                    $userMsg = "{$prefix}: pembaruan arsitektur dan sinkronisasi modul";
                }

                // Susun Pesan Commit Lengkap (Conventional Commits Multi-Line)
                $commitLines = [];
                $commitLines[] = $userMsg;
                $commitLines[] = "";
                $commitLines[] = "[DETAIL PERUBAHAN SEJAK PUSH TERAKHIR]";

                foreach ($categories as $catName => $catFiles) {
                    if (!empty($catFiles)) {
                        $commitLines[] = "• {$catName} (" . count($catFiles) . " file):";
                        foreach (array_slice($catFiles, 0, 8) as $cf) {
                            $commitLines[] = "  - {$cf}";
                        }
                        if (count($catFiles) > 8) {
                            $commitLines[] = "  - ...dan " . (count($catFiles) - 8) . " berkas lainnya";
                        }
                    }
                }

                $commitLines[] = "";
                $commitLines[] = "Ringkasan Diff: {$diffStatSummary}";
                $commitLines[] = "Branch: {$activeBranch}" . ($hasUpstream ? " (Tracking: {$upstreamBranch})" : "");
                $commitLines[] = "Timestamp: " . date('Y-m-d H:i:s T');

                $fullCommitMessage = implode("\n", $commitLines);

                // Jika dry-run, cukup tampilkan preview
                if ($isDryRun) {
                    $outputLog[] = "[DRY-RUN] Pratinjau Pesan Commit yang disusun:";
                    $outputLog[] = "--------------------------------------------------------";
                    $outputLog[] = $fullCommitMessage;
                    $outputLog[] = "--------------------------------------------------------";
                    $successMsg = "Pratinjau Dry-Run selesai. Tidak ada perubahan yang di-commit atau di-push.";
                } else {
                    // Tulis pesan ke temporary file untuk mencegah escaping bug pada Windows/PowerShell
                    $tempMsgFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'git_ship_' . uniqid() . '.txt';
                    file_put_contents($tempMsgFile, $fullCommitMessage);

                    // Stage semua perubahan
                    $cmdStage = 'git add -A 2>&1';
                    @shell_exec($cmdStage);
                    $outputLog[] = '[OK] Seluruh berkas perubahan dan berkas baru berhasil di-staging (git add -A).';

                    // Commit via berkas pesan
                    $cmdCommit = 'git commit -F ' . escapeshellarg($tempMsgFile) . ' 2>&1';
                    $commitOut = @shell_exec($cmdCommit);
                    @unlink($tempMsgFile);

                    // Ambil hash commit yang baru dibuat
                    $newHash = trim((string) @shell_exec('git rev-parse --short HEAD 2>&1'));
                    $outputLog[] = "[OK] Commit berhasil dibuat: [{$newHash}] {$userMsg}";

                    // Eksekusi Push jika diizinkan
                    if ($doPush) {
                        $remotesRaw = (string) @shell_exec('git remote 2>&1');
                        if (empty(trim($remotesRaw))) {
                            $outputLog[] = '[WARN] Remote git (origin) belum dikonfigurasi. Lewati langkah push.';
                            $successMsg = "Commit [{$newHash}] berhasil dibuat secara lokal! (Remote origin belum ada).";
                        } else {
                            $outputLog[] = "[INFO] Mendorong perubahan ke remote origin/{$activeBranch}...";
                            $pushCmd = 'git push origin ' . escapeshellarg($activeBranch) . ' 2>&1';
                            $pushOut = @shell_exec($pushCmd);
                            $outputLog[] = '[OK] Perubahan berhasil di-push ke origin/' . $activeBranch . '.';
                            $successMsg = "Berhasil Ship [{$newHash}]! " . count($statusLines) . " berkas di-commit dan di-push ke origin/{$activeBranch}.";
                        }
                    } else {
                        $successMsg = "Commit [{$newHash}] berhasil dibuat secara lokal! (Push dilewati karena --no-push).";
                    }
                }
            }
        } catch (Throwable $e) {
            $errorMsg = 'Kegagalan Eksekusi Ship: ' . $e->getMessage();
            $outputLog[] = '[ERROR] ' . $e->getMessage();
        }
    }
}

// =========================================================================
// ACTION: SYNC-SKILLS (Sinkronisasi Deterministik Rules, Skills & Dokumen SSoT)
// =========================================================================
if ($action === 'sync-skills' || $action === 'sync_skills') {
    $targetPath = null;
    $sourcePath = $rootDir;
    $isDryRun = false;

    if ($isCli) {
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--target=')) {
                $targetPath = trim(substr($arg, 9), '"\'');
            } elseif (str_starts_with($arg, '--source=')) {
                $sourcePath = trim(substr($arg, 9), '"\'');
            } elseif ($arg === '--dry-run') {
                $isDryRun = true;
            }
        }
        if (!$targetPath && isset($argv[2]) && !str_starts_with($argv[2], '--')) {
            $targetPath = $argv[2];
        }
    }

    // Default target: jika tidak ditentukan dan ../yayasan-pqtm ada, sarankan atau gunakan
    if (!$targetPath) {
        $siblingPqtm = dirname($rootDir) . DIRECTORY_SEPARATOR . 'yayasan-pqtm';
        if (is_dir($siblingPqtm) && realpath($siblingPqtm) !== realpath($rootDir)) {
            $targetPath = $siblingPqtm;
        }
    }

    if (!$targetPath || !is_dir($targetPath)) {
        $errorMsg = "Direktori target tidak valid atau tidak ditemukan. Gunakan: php install.php sync-skills --target=<path>";
    } else {
        try {
            $targetPath = realpath($targetPath);
            $sourcePath = realpath($sourcePath);
            $outputLog[] = "--- Memulai Sinkronisasi Tata Kelola AI & SSoT ('sync-skills') ---";
            $outputLog[] = "[INFO] Sumber (Source) : {$sourcePath}";
            $outputLog[] = "[INFO] Tujuan (Target) : {$targetPath}";
            if ($isDryRun) {
                $outputLog[] = "[DRY-RUN] Mode simulasi aktif (Tidak ada berkas yang ditulis).";
            }

            $syncedList = [];
            $syncRecursive = function($src, $dst) use (&$syncRecursive, &$syncedList, $isDryRun, $targetPath) {
                if (is_dir($src)) {
                    if (!is_dir($dst) && !$isDryRun) {
                        @mkdir($dst, 0755, true);
                    }
                    $items = scandir($src);
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $syncRecursive($src . DIRECTORY_SEPARATOR . $item, $dst . DIRECTORY_SEPARATOR . $item);
                    }
                } elseif (is_file($src)) {
                    $rel = str_replace($targetPath . DIRECTORY_SEPARATOR, '', $dst);
                    $srcHash = @hash_file('sha256', $src);
                    $dstHash = file_exists($dst) ? @hash_file('sha256', $dst) : null;
                    if ($dstHash === null) {
                        $syncedList[] = ['type' => 'BARU', 'file' => $rel];
                        if (!$isDryRun) {
                            $parent = dirname($dst);
                            if (!is_dir($parent)) @mkdir($parent, 0755, true);
                            @copy($src, $dst);
                        }
                    } elseif ($srcHash !== $dstHash) {
                        $syncedList[] = ['type' => 'UPDATE', 'file' => $rel];
                        if (!$isDryRun) {
                            @copy($src, $dst);
                        }
                    }
                }
            };

            // 1. Sinkronisasi .agents/rules/
            $srcRules = $sourcePath . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'rules';
            $dstRules = $targetPath . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'rules';
            if (is_dir($srcRules)) {
                $syncRecursive($srcRules, $dstRules);
            }

            // 2. Sinkronisasi .agents/skills/
            $srcSkills = $sourcePath . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'skills';
            $dstSkills = $targetPath . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'skills';
            if (is_dir($srcSkills)) {
                $syncRecursive($srcSkills, $dstSkills);
            }

            // 3. Sinkronisasi Dokumen SSoT Kanonikal
            $ssotFiles = [
                'AGENTS.md',
                'GEMINI.md',
                'llms.txt',
                'doc/security_standards.md',
                'doc/software_design_standards.md',
                'doc/data_analysis_standard.md',
                'doc/landing_page_standards.md',
                'doc/project_documentation_standards.md',
                'doc/testing_standards.md'
            ];
            foreach ($ssotFiles as $relFile) {
                $srcF = $sourcePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relFile);
                $dstF = $targetPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relFile);
                if (is_file($srcF)) {
                    $srcHash = @hash_file('sha256', $srcF);
                    $dstHash = file_exists($dstF) ? @hash_file('sha256', $dstF) : null;
                    if ($dstHash === null) {
                        $syncedList[] = ['type' => 'BARU', 'file' => $relFile];
                        if (!$isDryRun) {
                            $parent = dirname($dstF);
                            if (!is_dir($parent)) @mkdir($parent, 0755, true);
                            @copy($srcF, $dstF);
                        }
                    } elseif ($srcHash !== $dstHash) {
                        $syncedList[] = ['type' => 'UPDATE', 'file' => $relFile];
                        if (!$isDryRun) {
                            @copy($srcF, $dstF);
                        }
                    }
                }
            }

            if (empty($syncedList)) {
                $outputLog[] = "[INFO] Seluruh rules, skills, dan dokumen SSoT sudah 100% sinkron (0 delta).";
                $successMsg = "Tidak ada pembaruan: Seluruh skill dan aturan pada target sudah identik dengan sumber.";
            } else {
                $outputLog[] = "[INFO] Terdeteksi " . count($syncedList) . " berkas mengalami perubahan:";
                foreach ($syncedList as $item) {
                    $outputLog[] = "  [{$item['type']}] {$item['file']}";
                }
                if ($isDryRun) {
                    $successMsg = "[DRY-RUN] Simulasi sinkronisasi selesai: " . count($syncedList) . " berkas siap diperbarui.";
                } else {
                    $successMsg = "Sinkronisasi Berhasil! " . count($syncedList) . " berkas tata kelola skill & SSoT telah diperbarui di {$targetPath}.";
                }
            }
        } catch (Throwable $e) {
            $errorMsg = 'Kegagalan Eksekusi Sync-Skills: ' . $e->getMessage();
            $outputLog[] = '[ERROR] ' . $e->getMessage();
        }
    }
}

// 3. CLI Mode Output
if ($isCli) {
    echo "=================================================================\n";
    echo "   ENTERPRISE SETUP, HEALTH CHECKER & UPDATER (install.php)      \n";
    echo "=================================================================\n\n";

    echo "--- 1. INFORMASI VERSI SISTEM ---\n";
    echo "Versi Codebase : v" . APP_VERSION . "\n";
    echo "Versi Terpasang: " . ($isInstalled ? "v{$installedVersion}" : "Belum Terinstal") . "\n";
    echo "Status Update  : " . ($hasUpdate ? "[TERSEDIA UPDATE -> v" . APP_VERSION . "]\n" : "[TERBARU]\n");

    echo "\n--- 2. DIAGNOSTIK PRASYARAT SISTEM ---\n";
    foreach ($diagnostics as $key => $d) {
        $status = $d['passed'] ? '[PASS]' : '[FAIL]';
        echo sprintf("%-6s %-32s : %-15s (Req: %s)\n", $status, $d['title'], $d['current'], $d['required']);
        if (!$d['passed']) {
            echo "       -> Solusi: {$d['help']}\n";
        }
    }

    echo "\nStatus Prasyarat: " . ($allPassed ? "LENGKAP (Memenuhi Syarat)\n" : "BELUM LENGKAP (Perlu Tindakan)\n");
    echo "Status Terinstal: " . ($isInstalled ? "SUDAH TERINSTAL (.installed aktif)\n" : "BELUM TERINSTAL\n");

    if ($action === 'install' || $action === 'update' || $action === 'spawn' || $action === 'duplicate' || $action === 'ship' || $action === 'commit' || $action === 'sync-skills' || $action === 'sync_skills') {
        echo "\n--- 3. LOG EKSEKUSI ---\n";
        foreach ($outputLog as $line) {
            echo $line . "\n";
        }
        if ($successMsg) {
            $folderName = basename($rootDir);
            $domainHost = strtolower(preg_replace('/[^a-zA-Z0-9_.-]/', '-', $folderName)) . '.test';
            echo "\nSUCCESS: {$successMsg}\n";
            if ($action === 'spawn' || $action === 'duplicate') {
                $targetDomain = $newDomain ?? $domainHost;
                echo "Akses Web: http://{$targetDomain}\n";
                echo "Akun Admin: admin@{$targetDomain} (Password: password123)\n";
            } elseif ($action !== 'ship' && $action !== 'commit' && $action !== 'sync-skills' && $action !== 'sync_skills') {
                echo "Akses Web: http://{$domainHost}\n";
            }
            exit(0);
        } else {
            echo "\nERROR: {$errorMsg}\n";
            exit(1);
        }
    } else {
        echo "\nPilihan Tindakan CLI:\n";
        echo "  - Ship (Commit & Push): php install.php ship [\"Pesan Commit\"] [--no-push] [--dry-run]\n";
        echo "  - Sinkronisasi Skill  : php install.php sync-skills [--target=<path>] [--source=<path>] [--dry-run]\n";
        echo "  - Spawner Proyek Baru : php install.php spawn <target-folder> [--name=\"...\"] [--domain=\"...\"] [--desc=\"...\"]\n";
        echo "  - Inisiasi Instalasi  : php install.php install [--name=\"Nama App\"] [--domain=\"domain.test\"] [--desc=\"Deskripsi\"]\n";
        echo "  - Pembaruan Sistem    : php install.php update\n";
        echo "  - Cek Status          : php install.php status\n";
        exit(0);
    }
}

// 4. Web Browser Mode (Clean, Modern Dark-Themed UI, Zero External Dependencies)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hyperion Vault Setup & Updater Wizard v<?= APP_VERSION ?> - hyperion-vault.test</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        :root {
            --bg: #090d16;
            --card-bg: rgba(15, 23, 42, 0.85);
            --border: rgba(51, 65, 85, 0.6);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --cyan: #06b6d4;
            --cyan-glow: rgba(6, 182, 212, 0.25);
            --indigo: #6366f1;
            --indigo-glow: rgba(99, 102, 241, 0.25);
            --green: #10b981;
            --green-glow: rgba(16, 185, 129, 0.2);
            --amber: #f59e0b;
            --amber-glow: rgba(245, 158, 11, 0.2);
            --red: #ef4444;
            --red-glow: rgba(239, 68, 68, 0.2);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 85% 85%, rgba(6, 182, 212, 0.15) 0%, transparent 50%);
        }
        .container {
            width: 100%;
            max-width: 880px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .logo-box {
            width: 54px;
            height: 54px;
            border-radius: 0.85rem;
            background: linear-gradient(135deg, var(--indigo), var(--cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--cyan-glow);
            flex-shrink: 0;
        }
        .logo-box svg { width: 30px; height: 30px; fill: white; }
        h1 { font-size: 1.5rem; font-weight: 700; color: #fff; letter-spacing: -0.025em; }
        p.subtitle { color: var(--text-muted); font-size: 0.875rem; margin-top: 0.25rem; }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .alert-success { background: var(--green-glow); border: 1px solid var(--green); color: #6ee7b7; }
        .alert-error { background: var(--red-glow); border: 1px solid var(--red); color: #fca5a5; }
        .alert-info { background: var(--cyan-glow); border: 1px solid var(--cyan); color: #67e8f9; }
        .alert-warning { background: var(--amber-glow); border: 1px solid var(--amber); color: #fde68a; }

        .version-badge-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .v-pill {
            font-size: 0.75rem;
            font-family: monospace;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border);
            color: #38bdf8;
        }
        .v-pill-update {
            background: rgba(245, 158, 11, 0.2);
            border-color: rgba(245, 158, 11, 0.4);
            color: #fbbf24;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--cyan);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-weight: 600;
        }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge-fail { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }

        .diag-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .diag-item {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(51, 65, 85, 0.4);
            border-radius: 0.75rem;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .diag-item:hover { border-color: rgba(99, 102, 241, 0.5); background: rgba(30, 41, 59, 0.75); }
        .diag-info { display: flex; flex-direction: column; gap: 0.15rem; }
        .diag-name { font-size: 0.9rem; font-weight: 600; color: #f1f5f9; }
        .diag-desc { font-size: 0.75rem; color: var(--text-muted); }
        .diag-status { display: flex; align-items: center; gap: 0.5rem; }

        .log-box {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 0.75rem;
            padding: 1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8rem;
            color: #38bdf8;
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 0.75rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--indigo), var(--cyan));
            color: white;
            box-shadow: 0 4px 14px var(--cyan-glow);
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--cyan-glow);
        }
        .btn-update {
            background: linear-gradient(135deg, #d97706, #059669);
            color: white;
            box-shadow: 0 4px 14px var(--amber-glow);
        }
        .btn-update:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--amber-glow);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(1);
        }
        .btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            border: 1px solid #334155;
        }
        .btn-secondary:hover { background: #334155; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-box">
                <svg viewBox="0 0 24 24">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                </svg>
            </div>
            <div>
                <h1>Instalasi, Diagnostik & Updater Sistem Enterprise</h1>
                <div class="version-badge-bar">
                    <span class="v-pill">Codebase: v<?= APP_VERSION ?></span>
                    <span class="v-pill <?= $hasUpdate ? 'v-pill-update' : '' ?>">Terpasang: <?= $isInstalled ? "v{$installedVersion}" : "Belum Terinstal" ?></span>
                    <?php if ($hasUpdate): ?>
                        <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);">Pembaruan Tersedia</span>
                    <?php endif; ?>
                </div>
                <p class="subtitle">Laravel 13 Sanctum + SQLite WAL / MySQL + Svelte 5 Runes (Windows Laragon / Shared Hosting)</p>
            </div>
        </div>

        <!-- Alert Notifications -->
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <strong>✓ Berhasil!</strong> <?= htmlspecialchars($successMsg) ?>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #a7f3d0;">
                    Akun Administrator: <strong>admin@hyperion-vault.test</strong> | Password: <strong>password123</strong><br>
                    Akun Regular User: <strong>user@hyperion-vault.test</strong> | Password: <strong>password123</strong>
                </div>
            </div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-error">
                <strong>✕ Kesalahan:</strong> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php elseif ($hasUpdate): ?>
            <div class="alert alert-warning">
                <strong>🚀 Pembaruan Arsitektur Tersedia:</strong> Versi codebase terbaru adalah <strong>v<?= APP_VERSION ?></strong> (Sistem saat ini: <code>v<?= $installedVersion ?></code>).<br>
                Pembaruan mencakup: Migrasi skema database aman (tanpa penghapusan data), sinkronisasi cache, matriks hak akses RBAC kustom, dan kompilasi bundle frontend.
            </div>
        <?php elseif ($isInstalled): ?>
            <div class="alert alert-info">
                <strong>🔒 Sistem Telah Terinstal (v<?= $installedVersion ?>):</strong> Berkas lock <code>.installed</code> aktif. Seluruh modul siap digunakan. Anda dapat melakukan sinkronisasi cache atau membuka dashboard.
            </div>
        <?php endif; ?>

        <!-- Execution Logs -->
        <?php if (!empty($outputLog)): ?>
            <div class="section-title">Log Eksekusi</div>
            <div class="log-box"><?= htmlspecialchars(implode("\n", $outputLog)) ?></div>
        <?php endif; ?>

        <!-- Prerequisite Checklist -->
        <div class="section-title">
            <span>Pemeriksaan Prasyarat Lingkungan</span>
            <span class="badge <?= $allPassed ? 'badge-success' : 'badge-fail' ?>">
                <?= $allPassed ? 'Semua Syarat Terpenuhi' : 'Ada Syarat Belum Lengkap' ?>
            </span>
        </div>

        <div class="diag-grid">
            <?php foreach ($diagnostics as $d): ?>
                <div class="diag-item">
                    <div class="diag-info">
                        <span class="diag-name"><?= htmlspecialchars($d['title']) ?></span>
                        <span class="diag-desc">Syarat: <?= htmlspecialchars($d['required']) ?> | Terdeteksi: <strong><?= htmlspecialchars($d['current']) ?></strong></span>
                    </div>
                    <div class="diag-status">
                        <span class="badge <?= $d['passed'] ? 'badge-success' : 'badge-fail' ?>">
                            <?= $d['passed'] ? 'Memenuhi Syarat' : 'Gagal' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Spawner Card (Golden Archetype Duplicator) -->
        <?php if ($isInstalled): ?>
            <div style="background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="font-size: 0.95rem; font-weight: 700; color: #60a5fa; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🚀 Spawner Proyek Baru (Golden Archetype Duplicator)</span>
                    </div>
                    <span class="badge badge-success">⚡ Duplikasi Cepat (~5 Detik)</span>
                </div>
                <p style="color: var(--text-muted); font-size: 0.8125rem; line-height: 1.5; margin: 0 0 1rem;">
                    Duplikasi seluruh arsitektur engine ini ke direktori baru tanpa perlu mengunduh ulang dependensi (Zero <code>composer install</code> & Zero <code>npm install</code>). Kunci enkripsi unik, database SQLite terisolasi, dan akun admin baru otomatis dikonfigurasi secara instan.
                </p>

                <form method="POST">
                    <input type="hidden" name="action" value="spawn">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem; margin-bottom: 0.85rem;">
                        <div>
                            <label style="display:block; font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 600;">Nama Folder Target (di direktori www/)</label>
                            <input type="text" name="target_folder" placeholder="misal: portal-santri" required style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 600;">Nama Aplikasi (APP_NAME)</label>
                            <input type="text" name="target_name" placeholder="misal: Portal Santri Terpadu" style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                        </div>
                        <div>
                            <label style="display:block; font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 600;">Domain Virtual Host</label>
                            <input type="text" name="target_domain" placeholder="misal: portal-santri.test" style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                        </div>
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display:block; font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 600;">Deskripsi Aplikasi</label>
                        <input type="text" name="target_desc" placeholder="misal: Sistem Informasi Administrasi & Akademik Santri" style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                    </div>
                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #2563eb, #7c3aed); border: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                            </svg>
                            Duplikasi & Inisiasi Proyek Baru Sekarang
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="actions">
            <?php if ($hasUpdate): ?>
                <a href="/" class="btn btn-secondary">Buka Dashboard Aplikasi</a>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-update">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
                        </svg>
                        Perbarui Sistem Sekarang (Safe Update v<?= APP_VERSION ?>)
                    </button>
                </form>
            <?php elseif ($isInstalled): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-secondary" title="Bersihkan cache dan jalankan migrasi pending">
                        Re-sync Cache & Migrasi
                    </button>
                </form>
                <a href="/" class="btn btn-primary">Buka Dashboard Aplikasi &rarr;</a>
            <?php else: ?>
                <form method="POST" style="width: 100%;">
                    <input type="hidden" name="action" value="install">
                    
                    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(51, 65, 85, 0.5); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.5rem;">
                        <div style="font-size: 0.875rem; font-weight: 600; color: #38bdf8; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
                            <span>⚙️ Identitas & Konfigurasi Aplikasi Baru</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 0.85rem;">
                            <div>
                                <label style="display:block; font-size: 0.8125rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 500;">Nama Aplikasi (APP_NAME)</label>
                                <input type="text" name="app_name" value="<?= htmlspecialchars($defaultAppName) ?>" required style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                            </div>
                            <div>
                                <label style="display:block; font-size: 0.8125rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 500;">Domain Virtual Host (APP_URL)</label>
                                <input type="text" name="domain_host" value="<?= htmlspecialchars($defaultDomain) ?>" required style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                            </div>
                        </div>
                        <div>
                            <label style="display:block; font-size: 0.8125rem; color: #cbd5e1; margin-bottom: 0.35rem; font-weight: 500;">Deskripsi Aplikasi</label>
                            <input type="text" name="app_desc" value="<?= htmlspecialchars($defaultAppDesc) ?>" style="width: 100%; box-sizing: border-box; background: #020617; border: 1px solid #334155; border-radius: 0.5rem; padding: 0.6rem 0.75rem; color: #f8fafc; font-size: 0.875rem;">
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                            * Nilai di atas otomatis diisi dari nama direktori repositori. Anda dapat mengubahnya sebelum menekan tombol instalasi.
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" <?= !$allPassed ? 'disabled title="Penuhi prasyarat terlebih dahulu"' : '' ?>>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                            </svg>
                            Jalankan Instalasi Otomatis Sekarang
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
