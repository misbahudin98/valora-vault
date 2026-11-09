<?php
/**
 * Zero-Config Universal Gateway for Laragon Default Nginx & Apache
 * Serves Laravel API + Svelte 5 Frontend under a single domain (e.g. valora-vault.test)
 * without modifying any Nginx or Apache configuration files.
 */

// Security & Speed HTTP Headers (OWASP & ISO 27001)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

// 0. Automated Installer & Health Diagnostic Tool
if ($uri === '/install.php' || str_starts_with($uri, '/install.php/')) {
    require __DIR__ . '/install.php';
    exit;
}

// 0b. AI Engine Discovery (RFC llms.txt standard for AEO & AI Search)
if ($uri === '/llms.txt' && file_exists(__DIR__ . '/llms.txt')) {
    serveStaticFile(__DIR__ . '/llms.txt');
    exit;
}

// Auto-detect uninstalled state and redirect browser requests to /install.php
if (!file_exists(__DIR__ . '/.installed') && (!file_exists(__DIR__ . '/backend/.env') || !file_exists(__DIR__ . '/backend/database/database.sqlite'))) {
    if (!str_starts_with($uri, '/api/') && !str_starts_with($uri, '/sanctum/')) {
        header('Location: /install.php');
        exit;
    }
}

// 1. API & Sanctum routes -> Forward directly to Laravel backend
if (str_starts_with($uri, '/api/') || str_starts_with($uri, '/sanctum/') || $uri === '/up') {
    require __DIR__ . '/backend/public/index.php';
    exit;
}

// 2. Storage files -> Forward to Laravel storage
if (str_starts_with($uri, '/storage/')) {
    $storageFile = __DIR__ . '/backend/public' . $uri;
    if (file_exists($storageFile) && !is_dir($storageFile)) {
        serveStaticFile($storageFile);
    }
    require __DIR__ . '/backend/public/index.php';
    exit;
}

// 3. Static files in backend/public
$backendPublicFile = __DIR__ . '/backend/public' . $uri;
if ($uri !== '/' && file_exists($backendPublicFile) && !is_dir($backendPublicFile)) {
    serveStaticFile($backendPublicFile);
}

// 4. Static files in frontend/build or frontend/static
$frontendBuildFile = __DIR__ . '/frontend/build' . $uri;
if ($uri !== '/' && file_exists($frontendBuildFile) && !is_dir($frontendBuildFile)) {
    serveStaticFile($frontendBuildFile);
}

// 5. Development Mode: Proxy to Vite dev server if running on 127.0.0.1:5173
$vitePort = 5173;
$viteSocket = @fsockopen('127.0.0.1', $vitePort, $errno, $errstr, 0.1);
if ($viteSocket) {
    fclose($viteSocket);
    proxyToVite("http://127.0.0.1:{$vitePort}" . $_SERVER['REQUEST_URI']);
    exit;
}

// 6. Production / Static Build fallback: Serve frontend/build/index.html
$indexHtml = __DIR__ . '/frontend/build/index.html';
if (file_exists($indexHtml)) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $acceptsBr = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'br');
    $acceptsGzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');

    if ($acceptsBr && file_exists($indexHtml . '.br')) {
        header('Content-Encoding: br');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . filesize($indexHtml . '.br'));
        readfile($indexHtml . '.br');
        exit;
    }

    if ($acceptsGzip && file_exists($indexHtml . '.gz')) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . filesize($indexHtml . '.gz'));
        readfile($indexHtml . '.gz');
        exit;
    }

    if ($acceptsGzip && function_exists('gzencode')) {
        $compressed = gzencode(file_get_contents($indexHtml), 6);
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . strlen($compressed));
        echo $compressed;
        exit;
    }

    readfile($indexHtml);
    exit;
}

// 7. Fallback to Laravel if API/service route, or show Diagnostic Screen if browser HTML request
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (str_contains($acceptHeader, 'text/html') && !file_exists($indexHtml)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    $appName = basename(__DIR__);
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontend Belum Terkompilasi — {$appName}</title>
    <style>
        :root { --bg: #090d16; --card: #111827; --border: #1f2937; --text: #f3f4f6; --muted: #9ca3af; --accent: #3b82f6; --amber: #f59e0b; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 1rem; max-width: 600px; width: 100%; padding: 2.25rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .badge { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(245, 158, 11, 0.15); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.3); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8125rem; font-weight: 600; margin-bottom: 1.25rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.75rem; color: #fff; }
        p { color: var(--muted); font-size: 0.9375rem; line-height: 1.6; margin: 0 0 1.5rem; }
        .step { background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem 1.25rem; margin-bottom: 1rem; }
        .step h2 { font-size: 0.875rem; font-weight: 600; margin: 0 0 0.5rem; color: #fff; display: flex; align-items: center; justify-content: space-between; }
        code { background: #000; color: #34d399; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.875rem; padding: 0.35rem 0.6rem; border-radius: 0.375rem; display: block; border: 1px solid #1f2937; margin-top: 0.35rem; }
        .footer { margin-top: 1.5rem; font-size: 0.8125rem; color: var(--muted); text-align: center; }
        .footer a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">⚡ Menunggu Kompilasi Frontend</div>
        <h1>Frontend SPA Belum Siap</h1>
        <p>Aplikasi backend Laravel 13 dan gateway gateway aktif, namun bundle frontend Svelte 5 belum dikompilasi atau Vite dev server belum berjalan.</p>
        
        <div class="step">
            <h2>1. Jalankan Mode Development (Hot-Reload) <span>Rekomendasi</span></h2>
            <p style="margin:0 0 0.5rem; font-size:0.8125rem;">Buka terminal di folder proyek ini dan jalankan:</p>
            <code>cd frontend && npm run dev</code>
        </div>

        <div class="step">
            <h2>2. Atau Kompilasi Bundle Produksi</h2>
            <p style="margin:0 0 0.5rem; font-size:0.8125rem;">Jika ingin menjalankan versi build statis tanpa Vite server:</p>
            <code>cd frontend && npm install && npm run build</code>
        </div>

        <div class="footer">
            Perlu pemeriksaan prasyarat sistem? Buka <a href="/install.php">Automated System Health Checker & Installer</a>.
        </div>
    </div>
</body>
</html>
HTML;
    exit;
}

require __DIR__ . '/backend/public/index.php';

function serveStaticFile($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'xml' => 'application/xml; charset=UTF-8',
        'txt' => 'text/plain; charset=UTF-8',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $mime = $mimes[$ext] ?? (function_exists('mime_content_type') ? @mime_content_type($path) : 'application/octet-stream') ?: 'application/octet-stream';
    header("Content-Type: {$mime}");

    // Cache-Control Header: Immutable for Vite fingerprinted assets, no-cache for version manifest & html, standard for others
    $normalizedPath = str_replace('\\', '/', $path);
    if (str_contains($normalizedPath, '_app/immutable/')) {
        header('Cache-Control: public, max-age=31536000, immutable');
    } elseif ($ext === 'html' || str_ends_with($normalizedPath, 'version.json')) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    } else {
        header('Cache-Control: public, max-age=86400');
    }

    // ETag & Conditional 304 Not Modified
    $etag = '"' . md5(filemtime($path) . '-' . filesize($path)) . '"';
    header("ETag: {$etag}");
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }

    // Serve Pre-compressed Brotli or Gzip Asset if available and supported by browser
    $acceptsBr = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'br');
    $acceptsGzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
    $brPath = $path . '.br';
    $gzPath = $path . '.gz';

    if ($acceptsBr && file_exists($brPath)) {
        header('Content-Encoding: br');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . filesize($brPath));
        readfile($brPath);
        exit;
    }

    if ($acceptsGzip && file_exists($gzPath)) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . filesize($gzPath));
        readfile($gzPath);
        exit;
    }

    // Dynamic Gzip compression for compressible text assets
    $compressible = ['js', 'css', 'json', 'svg', 'txt', 'html'];
    if ($acceptsGzip && in_array($ext, $compressible) && filesize($path) > 1024 && function_exists('gzencode')) {
        $compressed = gzencode(file_get_contents($path), 6);
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . strlen($compressed));
        echo $compressed;
        exit;
    }

    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function proxyToVite($url) {
    $ch = curl_init($url);
    $headers = [];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) !== 'host' && strtolower($key) !== 'content-length') {
                $headers[] = "{$key}: {$value}";
            }
        }
    }
    $headers[] = 'Host: localhost:5173';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        http_response_code(502);
        echo "Failed to connect to Vite dev server: " . curl_error($ch);
        curl_close($ch);
        return;
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    http_response_code($httpCode);
    foreach (explode("\r\n", $headerStr) as $header) {
        $lower = strtolower($header);
        if (!empty($header) && !str_starts_with($lower, 'transfer-encoding:') && !str_starts_with($lower, 'content-length:')) {
            header($header, false);
        }
    }
    echo $body;
}
