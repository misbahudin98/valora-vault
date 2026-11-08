# Panduan Praktis HTTP Security Headers & Content Security Policy (CSP Level 3)
> **Engine:** Hyperion Vault v6.31.0 / v8.0.0 SDD-Enterprise-Deterministic  
> **Standar Rujukan:** W3C Content Security Policy Level 3, W3C Fetch Spec (CORS), RFC 6797 (HSTS), OWASP Secure Headers Project.

---

## 1. DAFTAR HTTP SECURITY HEADERS WAJIB

Setiap respons dari server web (baik Nginx Gateway maupun middleware Laravel `SecurityHeaders.php`) wajib menyertakan 7 header pertahanan berikut:

```http
Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
Cross-Origin-Opener-Policy: same-origin
```

---

## 2. BEDAH PERTAHANAN PER HEADER

### 2.1 Content-Security-Policy (CSP Level 3)
Mencegah injeksi kode berbahaya, eksekusi skrip pihak ketiga tanpa izin (XSS), dan pencurian data (*data exfiltration*):
- `default-src 'self'`: Seluruh aset hanya boleh dimuat dari domain origin yang sama secara default.
- `img-src 'self' data: https:`: Mengizinkan pemuatan gambar lokal, data URIs (SVG avatar), dan HTTPS terpercaya.
- `font-src 'self' data:`: Mengizinkan font lokal (Plus Jakarta Sans / Inter).
- `style-src 'self' 'unsafe-inline'`: Mengizinkan styling Tailwind CSS modern yang di-inject per komponen.
- `script-src 'self'`: Dilarang skrip eksternal pihak ketiga (`eval` atau inline script tanpa otorisasi).
- `object-src 'none'`: Mematikan plugin usang seperti Flash Player, Java Applet, ActiveX.
- `base-uri 'self'`: Mencegah penyerang mengubah tag `<base href="...">` untuk membajak relative URLs.
- `frame-ancestors 'none'`: Mencegah situs dibungkus dalam `<iframe>` di situs manapun (Pertahanan Anti-Clickjacking tingkat modern).

### 2.2 X-Frame-Options: DENY
- Pertahanan lapis ganda pelengkap CSP `frame-ancestors 'none'` untuk browser legasi yang belum mendukung CSP Level 2/3.
- Menolak rendering di dalam `<frame>`, `<iframe>`, `<embed>`, atau `<object>`.

### 2.3 X-Content-Type-Options: nosniff
- Menginstruksikan browser untuk **tidak menebak tipe MIME** berkas (*MIME sniffing*).
- Jika server mengirimkan `text/plain`, browser dilarang mengeksekusinya sebagai `text/javascript` meskipun berkas berisi kode JavaScript. Sangat krusial untuk fitur unggah berkas (*file upload*).

### 2.4 Referrer-Policy: strict-origin-when-cross-origin
- Mengirimkan full URL referrer hanya untuk request dalam satu origin yang sama.
- Untuk request lintas domain HTTPS, hanya mengirimkan nama domain origin (tanpa query string berisikan token sensitif).
- Tidak mengirimkan header referrer sama sekali jika beralih dari HTTPS ke HTTP.

### 2.5 Permissions-Policy (Feature-Policy)
- Mematikan akses API peramban terhadap sensor dan perangkat keras fisik yang tidak diperlukan oleh aplikasi:
  - `camera=()`: Akses kamera dimatikan.
  - `microphone=()`: Akses mikrofon dimatikan.
  - `geolocation=()`: Akses koordinat GPS fisik dimatikan.
  - `payment=()`: Web Payment API dimatikan.

### 2.6 Strict-Transport-Security (HSTS - RFC 6797)
- Memaksa browser hanya berkomunikasi menggunakan saluran aman HTTPS selama minimal 1 tahun (`max-age=31536000`).
- `includeSubDomains`: Berlaku untuk seluruh subdomain.
- `preload`: Memenuhi syarat untuk didaftarkan ke daftar HSTS Preload global peramban Chromium & Firefox.

---

## 3. IMPLEMENTASI LARAVEL & NGINX GATEWAY

### Implementasi Middleware Laravel (`backend/app/Http/Middleware/SecurityHeaders.php`):
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data: https:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
```

### Implementasi Universal Nginx Gateway (`index.php` / Laragon Nginx):
Pada level gateway web server Nginx:
```nginx
add_header X-Frame-Options "DENY" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```
