<?php
/**
 * VALORA VAULT — Automated Semantic Versioning & Documentation Sync Engine
 *
 * SSoT Version Synchronizer across 8 files:
 * 1. backend/composer.json
 * 2. frontend/package.json
 * 3. install.php
 * 4. backend/routes/api.php
 * 5. llms.txt
 * 6. README.md
 * 7. doc/architecture.md, doc/design.md, doc/prompt.feature.md, doc/feature.md
 * 8. doc/changes.md
 *
 * Usage:
 *   php doc/version_sync.php status
 *   php doc/version_sync.php bump patch [--changelog="Pesan perubahan"]
 *   php doc/version_sync.php bump minor [--changelog="Pesan perubahan"]
 *   php doc/version_sync.php bump major [--changelog="Pesan perubahan"]
 *   php doc/version_sync.php set 6.31.0 [--changelog="Pesan perubahan"]
 */

declare(strict_types=1);

$startTime = microtime(true);

echo "=================================================================\n";
echo "   🔄 VALORA VAULT — SEMANTIC VERSION & DOC SYNC ENGINE        \n";
echo "=================================================================\n\n";

$rootDir = dirname(__DIR__);
$composerJsonPath = $rootDir . '/backend/composer.json';
$packageJsonPath = $rootDir . '/frontend/package.json';
$installPhpPath = $rootDir . '/install.php';
$routesApiPath = $rootDir . '/backend/routes/api.php';
$llmsTxtPath = $rootDir . '/llms.txt';
$readmePath = $rootDir . '/README.md';
$docArchitecturePath = $rootDir . '/doc/architecture.md';
$docDesignPath = $rootDir . '/doc/design.md';
$docPromptPath = $rootDir . '/doc/prompt.feature.md';
$docFeaturePath = $rootDir . '/doc/feature.md';
$docLocalPath = $rootDir . '/doc/local.inisiasi.md';
$docChangesPath = $rootDir . '/doc/changes.md';

// Helper: Extract current version from composer.json
function getBaselineVersion(string $composerJsonPath): string {
    if (!file_exists($composerJsonPath)) {
        throw new RuntimeException("composer.json tidak ditemukan di {$composerJsonPath}");
    }
    $data = json_decode(file_get_contents($composerJsonPath), true);
    return $data['version'] ?? '6.30.0';
}

// Helper: Inspect versions from all files
function inspectVersions(array $files): array {
    $results = [];
    foreach ($files as $name => $info) {
        $path = $info['path'];
        $regex = $info['regex'];
        if (!file_exists($path)) {
            $results[$name] = ['path' => $path, 'version' => 'FILE NOT FOUND', 'ok' => false];
            continue;
        }
        $content = file_get_contents($path);
        if (preg_match($regex, $content, $matches)) {
            $results[$name] = ['path' => $path, 'version' => $matches[1], 'ok' => true];
        } else {
            $results[$name] = ['path' => $path, 'version' => 'NOT MATCHED', 'ok' => false];
        }
    }
    return $results;
}

// Helper: Inspect feature modules in doc/features/ and check index in doc/feature.md
function inspectFeatures(string $featuresDir, string $featureMdPath): array {
    if (!is_dir($featuresDir) || !file_exists($featureMdPath)) {
        return ['features' => [], 'all_indexed' => true];
    }
    $featureFiles = glob($featuresDir . '/*.md') ?: [];
    $featureMdContent = file_get_contents($featureMdPath);
    $results = [];
    $allIndexed = true;

    foreach ($featureFiles as $file) {
        $basename = basename($file);
        $content = file_get_contents($file);
        $title = 'Tanpa Judul';
        if (preg_match('/^#\s*(.+)$/m', $content, $m)) {
            $title = trim($m[1]);
        }
        $isIndexed = str_contains($featureMdContent, $basename);
        if (!$isIndexed) $allIndexed = false;
        $results[] = [
            'file' => $basename,
            'title' => $title,
            'indexed' => $isIndexed,
        ];
    }
    return ['features' => $results, 'all_indexed' => $allIndexed];
}

// Define Target Files and their Regexes
$fileDefinitions = [
    'backend/composer.json' => [
        'path' => $composerJsonPath,
        'regex' => '/"version":\s*"([^"]+)"/',
        'replace' => fn($ver, $c) => preg_replace('/("version":\s*")[^"]+(")/', "\${1}{$ver}\${2}", $c)
    ],
    'frontend/package.json' => [
        'path' => $packageJsonPath,
        'regex' => '/"version":\s*"([^"]+)"/',
        'replace' => fn($ver, $c) => preg_replace('/("version":\s*")[^"]+(")/', "\${1}{$ver}\${2}", $c)
    ],
    'install.php' => [
        'path' => $installPhpPath,
        'regex' => '/define\(\'APP_VERSION\',\s*\'([^\']+)\'\);/',
        'replace' => fn($ver, $c) => preg_replace('/(define\(\'APP_VERSION\',\s*\')[^\']+(\'\);)/', "\${1}{$ver}\${2}", $c)
    ],
    'backend/routes/api.php' => [
        'path' => $routesApiPath,
        'regex' => '/\$codebaseVersion\s*=\s*\'([^\']+)\';/',
        'replace' => fn($ver, $c) => preg_replace('/(\$codebaseVersion\s*=\s*\')[^\']+(\';)/', "\${1}{$ver}\${2}", $c)
    ],
    'llms.txt' => [
        'path' => $llmsTxtPath,
        'regex' => '/version:\s*"([^"]+)"/',
        'replace' => fn($ver, $c) => preg_replace('/(version:\s*")[^"]+(")/', "\${1}{$ver}\${2}", $c)
    ],
    'doc/architecture.md' => [
        'path' => $docArchitecturePath,
        'regex' => '/\(v([0-9]+\.[0-9]+\.[0-9]+)\s*-\s*SemVer 2\.0\.0\)/',
        'replace' => fn($ver, $c) => preg_replace('/\(v[0-9]+\.[0-9]+\.[0-9]+(\s*-\s*SemVer 2\.0\.0\))/', "(v{$ver}\${1})", $c)
    ],
    'doc/design.md' => [
        'path' => $docDesignPath,
        'regex' => '/\(v([0-9]+\.[0-9]+\.[0-9]+)\)/',
        'replace' => fn($ver, $c) => preg_replace('/(\(v)[0-9]+\.[0-9]+\.[0-9]+(\))/', "\${1}{$ver}\${2}", $c)
    ],
    'doc/prompt.feature.md' => [
        'path' => $docPromptPath,
        'regex' => '/\(Valora Vault v([0-9]+\.[0-9]+\.[0-9]+)\)/',
        'replace' => fn($ver, $c) => preg_replace('/(\(Valora Vault v)[0-9]+\.[0-9]+\.[0-9]+(\))/', "\${1}{$ver}\${2}", $c)
    ],
    'doc/feature.md' => [
        'path' => $docFeaturePath,
        'regex' => '/\(v([0-9]+\.[0-9]+\.[0-9]+)\)/',
        'replace' => fn($ver, $c) => preg_replace('/(\(v)[0-9]+\.[0-9]+\.[0-9]+(\))/', "\${1}{$ver}\${2}", $c)
    ],
];

// CLI Arguments
$action = $argv[1] ?? 'status';
$longOpts = getopt('', ['changelog::']);
$changelogMsg = $longOpts['changelog'] ?? '';

$currentBaseline = getBaselineVersion($composerJsonPath);

if ($action === 'status' || $action === 'check') {
    echo "Pemeriksaan Konsistensi Versi SemVer (Baseline: v{$currentBaseline}):\n\n";
    $inspections = inspectVersions($fileDefinitions);
    $allMatched = true;

    printf("%-26s | %-16s | %s\n", "Berkas Target", "Versi Terbaca", "Status Kepatuhan");
    echo str_repeat('-', 65) . "\n";

    foreach ($inspections as $name => $info) {
        $v = $info['version'];
        $match = ($v === $currentBaseline);
        if (!$match) $allMatched = false;
        $statusStr = $match ? "✓ LOCKSTEP (OK)" : "❌ DESINKRONISASI";
        printf("%-26s | %-16s | %s\n", $name, $v, $statusStr);
    }

    // Feature Module Audit
    $featuresDir = $rootDir . '/doc/features';
    $featureInspection = inspectFeatures($featuresDir, $docFeaturePath);
    echo "Audit Modul Spesifikasi Fitur (doc/features/*.md -> doc/feature.md):\n\n";
    printf("%-30s | %s\n", "Berkas Spesifikasi", "Indeks di doc/feature.md");
    echo str_repeat('-', 65) . "\n";
    foreach ($featureInspection['features'] as $f) {
        $statusStr = $f['indexed'] ? "✓ TERINDEKS (OK)" : "⚠️ BELUM TERINDEKS";
        printf("%-30s | %s\n", $f['file'], $statusStr);
    }
    echo "\n";
    if ($allMatched && $featureInspection['all_indexed']) {
        echo "  🎉 Seluruh 9 berkas versi dan " . count($featureInspection['features']) . " modul fitur berada dalam keselarasan absolut!\n";
        exit(0);
    } elseif (!$allMatched) {
        echo "  ⚠️ Terdeteksi desinkronisasi versi! Jalankan 'php doc/version_sync.php set {$currentBaseline}' untuk menyelaraskan.\n";
        exit(1);
    } else {
        echo "  ⚠️ Terdeteksi modul fitur di doc/features/ yang belum didaftarkan pada doc/feature.md!\n";
        exit(1);
    }
}

// Calculate New Version for Bump / Set
$newVersion = '';
if ($action === 'bump') {
    $bumpType = strtolower($argv[2] ?? 'patch');
    if (!in_array($bumpType, ['patch', 'minor', 'major'], true)) {
        echo "  [ERROR] Jenis bump tidak valid: '{$bumpType}'. Gunakan: patch, minor, atau major.\n";
        exit(1);
    }

    $parts = explode('.', $currentBaseline);
    $major = (int)($parts[0] ?? 6);
    $minor = (int)($parts[1] ?? 30);
    $patch = (int)($parts[2] ?? 0);

    if ($bumpType === 'patch') {
        $patch++;
    } elseif ($bumpType === 'minor') {
        $minor++;
        $patch = 0;
    } elseif ($bumpType === 'major') {
        $major++;
        $minor = 0;
        $patch = 0;
    }
    $newVersion = "{$major}.{$minor}.{$patch}";
} elseif ($action === 'set') {
    $targetVer = $argv[2] ?? '';
    if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $targetVer)) {
        echo "  [ERROR] Format versi SemVer tidak valid: '{$targetVer}'. Wajib MAJOR.MINOR.PATCH (misal: 6.31.0).\n";
        exit(1);
    }
    $newVersion = $targetVer;
} else {
    echo "Penggunaan Skrip:\n";
    echo "  php doc/version_sync.php status\n";
    echo "  php doc/version_sync.php bump [patch|minor|major] [--changelog=\"...\"]\n";
    echo "  php doc/version_sync.php set <version> [--changelog=\"...\"]\n";
    exit(1);
}

echo "Menaikkan Versi SemVer: v{$currentBaseline} -> v{$newVersion}...\n\n";

// Apply Replacement to All Target Files
$updatedCount = 0;
foreach ($fileDefinitions as $name => $info) {
    $path = $info['path'];
    $replaceFn = $info['replace'];
    if (!file_exists($path)) {
        echo "  [SKIP] Berkas {$name} tidak ditemukan.\n";
        continue;
    }
    $original = file_get_contents($path);
    $replaced = $replaceFn($newVersion, $original);
    if ($replaced !== null && $replaced !== $original) {
        file_put_contents($path, $replaced);
        echo "  ✓ {$name} berhasil diperbarui ke v{$newVersion}\n";
        $updatedCount++;
    } else {
        echo "  [INFO] {$name} sudah menggunakan v{$newVersion} atau tidak ada pola yang cocok.\n";
    }
}

// Append to doc/changes.md if changelog provided
if (!empty($changelogMsg) && file_exists($docChangesPath)) {
    $changesContent = file_get_contents($docChangesPath);
    $isoDate = date('Y-m-d');
    $logEntry = "\n---\n\n## Pembaruan Versi Semantik v{$newVersion} ({$isoDate})\n"
              . "* **Rasional & Deskripsi:** {$changelogMsg}\n"
              . "* **Otomatisasi:** Versi diselaraskan ke seluruh berkas repositori via `doc/version_sync.php`.\n";
    
    file_put_contents($docChangesPath, $changesContent . $logEntry);
    echo "  ✓ doc/changes.md berhasil ditambahkan log rilis v{$newVersion}\n";
}

$duration = round(microtime(true) - $startTime, 2);
echo "\n=================================================================\n";
echo "   🎉 SINKRONISASI SELESAI: v{$newVersion} AKTIF DI SELURUH DOKUMEN ({$duration}s) \n";
echo "=================================================================\n";
