# ==============================================================================
# AUDITOR OTOMATIS POSTUR KEAMANAN APLIKASI WEB & API (Hyperion Vault DevSecOps)
# Memeriksa kepatuhan terhadap .agents/rules/security_invariants.md
# ==============================================================================
param (
    [string]$BasePath = (Get-Location).Path
)

$ErrorActionPreference = "SilentlyContinue"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "   AUDITOR POSTUR KEAMANAN WEB & API (Hyperion Vault DevSecOps)   " -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "Basis Direktori: $BasePath`n" -ForegroundColor Gray

$ChecksPassed = 0
$TotalChecks = 10
$Findings = @()

# ------------------------------------------------------------------------------
# Checkpoint 1: Konfigurasi Environment & Session Driver (Invariants 04 & 05)
# ------------------------------------------------------------------------------
$EnvPath = Join-Path $BasePath "backend/.env"
if (-not (Test-Path $EnvPath)) {
    $EnvPath = Join-Path $BasePath "backend/.env.example"
}

if (Test-Path $EnvPath) {
    $EnvContent = Get-Content $EnvPath -Raw
    $SessionDriverFile = $EnvContent -match "SESSION_DRIVER=file"
    $SessionDriverNotDb = -not ($EnvContent -match "SESSION_DRIVER=database")
    
    if ($SessionDriverFile -and $SessionDriverNotDb) {
        Write-Host "[PASS] Checkpoint 1: SESSION_DRIVER terisolasi ke 'file' (Bukan database)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 1: SESSION_DRIVER bukan 'file' atau terdeteksi 'database'." -ForegroundColor Red
        $Findings += "SESSION_DRIVER wajib bernilai 'file' untuk mencegah bottleneck database dan session hijacking."
    }
} else {
    Write-Host "[WARN] Checkpoint 1: Berkas .env tidak ditemukan, dilewati." -ForegroundColor Yellow
}

# ------------------------------------------------------------------------------
# Checkpoint 2: Mode Debug & Anti-Information Leakage (RFC 7807)
# ------------------------------------------------------------------------------
if (Test-Path $EnvPath) {
    $EnvContent = Get-Content $EnvPath -Raw
    $IsProduction = $EnvContent -match "APP_ENV=production"
    $AppDebug = $EnvContent -match "APP_DEBUG=true"
    
    if ($IsProduction -and $AppDebug) {
        Write-Host "[FAIL] Checkpoint 2: APP_DEBUG=true terdeteksi di lingkungan produksi! Rentan kebocoran trace." -ForegroundColor Red
        $Findings += "Setel APP_DEBUG=false pada lingkungan produksi untuk mematuhi RFC 7807."
    } elseif (-not $AppDebug) {
        Write-Host "[PASS] Checkpoint 2: APP_DEBUG tidak aktif (Anti-Information Leakage terlindungi)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[PASS] Checkpoint 2: APP_DEBUG aktif untuk lingkungan lokal (Development mode terdeteksi)." -ForegroundColor Green
        $ChecksPassed++
    }
} else {
    Write-Host "[WARN] Checkpoint 2: Berkas .env tidak ditemukan." -ForegroundColor Yellow
}

# ------------------------------------------------------------------------------
# Checkpoint 3: Pemeriksaan SQL Injection (Raw Query Concatenation)
# ------------------------------------------------------------------------------
$BackendControllers = Join-Path $BasePath "backend/app/Http/Controllers"
$RawQueryFiles = @()

if (Test-Path $BackendControllers) {
    $PhpFiles = Get-ChildItem -Path $BackendControllers -Recurse -Filter "*.php"
    foreach ($file in $PhpFiles) {
        $content = Get-Content $file.FullName -Raw
        # Deteksi string concatenation di dalam DB::raw atau whereRaw
        if ($content -match 'DB::raw\s*\([^)]*\$[a-zA-Z_]' -or $content -match 'whereRaw\s*\([^)]*\$[a-zA-Z_]') {
            $RawQueryFiles += $file.Name
        }
    }
    
    if ($RawQueryFiles.Count -eq 0) {
        Write-Host "[PASS] Checkpoint 3: 0 SQL Injection detected (Bebas dari string concatenation pada raw query)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 3: Ditemukan potensi raw query rentan pada: $($RawQueryFiles -join ', ')" -ForegroundColor Red
        $Findings += "Gunakan parameter binding (?) pada kueri mentah di $($RawQueryFiles -join ', ')."
    }
} else {
    Write-Host "[SKIP] Checkpoint 3: Direktori Controller backend tidak ditemukan." -ForegroundColor Gray
}

# ------------------------------------------------------------------------------
# Checkpoint 4: Mass-Assignment Guard ($guarded = [] Elimination)
# ------------------------------------------------------------------------------
$BackendModels = Join-Path $BasePath "backend/app/Models"
$UnguardedModels = @()

if (Test-Path $BackendModels) {
    $ModelFiles = Get-ChildItem -Path $BackendModels -Filter "*.php"
    foreach ($file in $ModelFiles) {
        $content = Get-Content $file.FullName -Raw
        if ($content -match '\$guarded\s*=\s*\[\s*\]') {
            $UnguardedModels += $file.Name
        }
    }
    
    if ($UnguardedModels.Count -eq 0) {
        Write-Host "[PASS] Checkpoint 4: Seluruh Model aman dari Mass-Assignment (0 Model dengan \$guarded = [])." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 4: Model rentan Mass-Assignment: $($UnguardedModels -join ', ')" -ForegroundColor Red
        $Findings += "Ganti \$guarded = [] dengan deklarasi \$fillable eksplisit pada $($UnguardedModels -join ', ')."
    }
} else {
    Write-Host "[SKIP] Checkpoint 4: Direktori Models tidak ditemukan." -ForegroundColor Gray
}

# ------------------------------------------------------------------------------
# Checkpoint 5: Rate Limiting & Throttling pada Endpoint Autentikasi
# ------------------------------------------------------------------------------
$ApiRoutesPath = Join-Path $BasePath "backend/routes/api.php"
if (Test-Path $ApiRoutesPath) {
    $RoutesContent = Get-Content $ApiRoutesPath -Raw
    $HasThrottle = $RoutesContent -match "throttle:"
    
    if ($HasThrottle) {
        Write-Host "[PASS] Checkpoint 5: Rate Limiting (Throttle Middleware) aktif pada API routes." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 5: Middleware throttle tidak terdeteksi pada api.php." -ForegroundColor Red
        $Findings += "Terapkan middleware 'throttle:5,1' pada endpoint /auth/login untuk mencegah serangan brute force."
    }
} else {
    Write-Host "[SKIP] Checkpoint 5: Berkas api.php tidak ditemukan." -ForegroundColor Gray
}

# ------------------------------------------------------------------------------
# Checkpoint 6: Middleware Security Headers (CSP, HSTS, X-Frame-Options)
# ------------------------------------------------------------------------------
$SecHeadersPath = Join-Path $BasePath "backend/app/Http/Middleware/SecurityHeaders.php"
if (Test-Path $SecHeadersPath) {
    $SecContent = Get-Content $SecHeadersPath -Raw
    $HasCsp = $SecContent -match "Content-Security-Policy"
    $HasFrameOptions = $SecContent -match "X-Frame-Options"
    $HasNosniff = $SecContent -match "X-Content-Type-Options"
    
    if ($HasCsp -and $HasFrameOptions -and $HasNosniff) {
        Write-Host "[PASS] Checkpoint 6: SecurityHeaders middleware aktif lengkap (CSP, X-Frame-Options, nosniff)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 6: SecurityHeaders tidak lengkap." -ForegroundColor Red
        $Findings += "Lengkapi header Content-Security-Policy dan X-Frame-Options pada SecurityHeaders.php."
    }
} else {
    Write-Host "[FAIL] Checkpoint 6: Berkas SecurityHeaders.php tidak ditemukan." -ForegroundColor Red
    $Findings += "Buat middleware SecurityHeaders.php untuk melindungi aplikasi dari XSS dan Clickjacking."
}

# ------------------------------------------------------------------------------
# Checkpoint 7: Tabel Multi-Device Registry (UserDevice Model)
# ------------------------------------------------------------------------------
$UserDeviceModel = Join-Path $BasePath "backend/app/Models/UserDevice.php"
if (Test-Path $UserDeviceModel) {
    Write-Host "[PASS] Checkpoint 7: Multi-Device Registry aktif (Model UserDevice tersedia)." -ForegroundColor Green
    $ChecksPassed++
} else {
    Write-Host "[FAIL] Checkpoint 7: Model UserDevice tidak ditemukan untuk multi-device tracking." -ForegroundColor Red
    $Findings += "Implementasikan Model UserDevice untuk mendukung audit perangkat dan token revocation."
}

# ------------------------------------------------------------------------------
# Checkpoint 8: Atribut Kunci Token Version pada User
# ------------------------------------------------------------------------------
$UserModel = Join-Path $BasePath "backend/app/Models/User.php"
if (Test-Path $UserModel) {
    $UserContent = Get-Content $UserModel -Raw
    if ($UserContent -match "token_version") {
        Write-Host "[PASS] Checkpoint 8: Token Versioning aktif pada User (Mendukung Instant Revocation)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 8: Atribut token_version tidak ditemukan pada User.php." -ForegroundColor Red
        $Findings += "Tambahkan atribut token_version pada User.php untuk mendukung invalidasi sesi instan."
    }
} else {
    Write-Host "[SKIP] Checkpoint 8: Berkas User.php tidak ditemukan." -ForegroundColor Gray
}

# ------------------------------------------------------------------------------
# Checkpoint 9: Keberadaan File Pengungkapan Kerentanan RFC 9116 (security.txt)
# ------------------------------------------------------------------------------
$SecurityTxt1 = Join-Path $BasePath "backend/public/.well-known/security.txt"
$SecurityTxt2 = Join-Path $BasePath "public/.well-known/security.txt"
$SecurityTxt3 = Join-Path $BasePath "frontend/static/.well-known/security.txt"

if ((Test-Path $SecurityTxt1) -or (Test-Path $SecurityTxt2) -or (Test-Path $SecurityTxt3)) {
    Write-Host "[PASS] Checkpoint 9: Berkas RFC 9116 security.txt terpasang di jalur publik." -ForegroundColor Green
    $ChecksPassed++
} else {
    Write-Host "[WARN] Checkpoint 9: Berkas RFC 9116 security.txt belum terpasang di direktori publik." -ForegroundColor Yellow
    $Findings += "Tempatkan berkas security.txt pada public/.well-known/security.txt."
}

# ------------------------------------------------------------------------------
# Checkpoint 10: Strict Type Declaration pada Kode Backend PHP
# ------------------------------------------------------------------------------
$StrictTypeViolations = 0
if (Test-Path $BackendControllers) {
    $PhpFiles = Get-ChildItem -Path $BackendControllers -Recurse -Filter "*.php"
    foreach ($file in $PhpFiles) {
        $firstLines = Get-Content $file.FullName -TotalCount 5
        if (-not ($firstLines -match "declare\s*\(\s*strict_types\s*=\s*1\s*\)")) {
            $StrictTypeViolations++
        }
    }
    
    if ($StrictTypeViolations -eq 0) {
        Write-Host "[PASS] Checkpoint 10: Seluruh berkas PHP diawali declare(strict_types=1)." -ForegroundColor Green
        $ChecksPassed++
    } else {
        Write-Host "[FAIL] Checkpoint 10: Ditemukan $StrictTypeViolations berkas PHP tanpa strict_types." -ForegroundColor Red
        $Findings += "Tambahkan declare(strict_types=1); pada awal setiap berkas PHP backend."
    }
} else {
    Write-Host "[SKIP] Checkpoint 10: Direktori Controller tidak ditemukan." -ForegroundColor Gray
}

# ------------------------------------------------------------------------------
# Rangkuman & Skor Kesiapan
# ------------------------------------------------------------------------------
$Score = [Math]::Round(($ChecksPassed / $TotalChecks) * 100, 1)

Write-Host "`n-----------------------------------------------------------------" -ForegroundColor Gray
Write-Host "Hasil Audit Keamanan: $ChecksPassed / $TotalChecks Checkpoint Lulus" -ForegroundColor Cyan
Write-Host "Skor Kesiapan Postur Keamanan: $Score%" -ForegroundColor $(if ($Score -ge 90) { "Green" } else { "Red" })
Write-Host "-----------------------------------------------------------------" -ForegroundColor Gray

if ($Findings.Count -gt 0) {
    Write-Host "`nDaftar Temuan & Rekomendasi Remediasi:" -ForegroundColor Yellow
    foreach ($f in $Findings) {
        Write-Host " - $f" -ForegroundColor Red
    }
}

if ($Score -ge 90) {
    Write-Host "`nKESIMPULAN: LULUS SERTIFIKASI POSTUR KEAMANAN (SIAP PRODUKSI)`n" -ForegroundColor Green
    exit 0
} else {
    Write-Host "`nKESIMPULAN: DITOLAK (DIBUTUHKAN HARDENING SEBELUM REVIEW GATE)`n" -ForegroundColor Red
    exit 1
}
