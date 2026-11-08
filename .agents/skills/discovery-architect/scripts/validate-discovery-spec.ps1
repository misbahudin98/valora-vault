<#
.SYNOPSIS
    Validator teks spesifikasi Discovery & Intake Blueprint Studio.
.DESCRIPTION
    Memeriksa kepatuhan naskah intake terhadap:
    1. Ketiadaan kata sifat subjektif/ambigu (anti-slop check).
    2. Keberadaan formula JTBD (Sebagai..., ketika..., saya ingin..., sehingga...).
    3. Keberadaan 4 visual states (Loading, Empty, Error, Success).
.PARAMETER FilePath
    Path ke berkas markdown spesifikasi yang akan divalidasi.
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$FilePath
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $FilePath)) {
    Write-Error "Berkas tidak ditemukan: $FilePath"
    exit 1
}

$content = Get-Content -Path $FilePath -Raw
$issues = [System.Collections.Generic.List[string]]::new()

# 1. Cek kata sifat ambigu
$ambiguousWords = @("fleksibel", "mudah", "cepat", "canggih", "modern", "user-friendly", "seamless", "holistik", "sinergi")
foreach ($word in $ambiguousWords) {
    if ($content -match "\b$word\b") {
        $issues.Add("Terdeteksi kata sifat subjektif/ambigu: '$word'. Ganti dengan metrik teknis kuantitatif.")
    }
}

# 2. Cek formula JTBD
if (-not ($content -match "(?i)sebagai\s+.+,\s+ketika\s+.+,\s+saya\s+ingin\s+.+,\s+sehingga\s+")) {
    $issues.Add("Format formula Jobs-to-be-Done (JTBD) tidak lengkap atau tidak terdeteksi (Wajib memuat: 'Sebagai..., ketika..., saya ingin..., sehingga...').")
}

# 3. Cek 4 visual states
$states = @("loading", "empty", "error", "success")
foreach ($s in $states) {
    if (-not ($content -match "(?i)$s")) {
        $issues.Add("Pemetaan 4 visual state tidak lengkap. State '$s' tidak terdeteksi.")
    }
}

Write-Host "`n=== HASIL AUDIT DISCOVERY SPECIFICATION ===" -ForegroundColor Cyan
Write-Host "Target: $FilePath"

if ($issues.Count -eq 0) {
    Write-Host "`n[OK] VALIDASI SUKSES: Naskah intake siap dieksekusi di Blueprint Studio (100% Lulus)." -ForegroundColor Green
    exit 0
} else {
    Write-Host "`n[PERINGATAN] DITEMUKAN $($issues.Count) CATATAN KELAYAKAN:" -ForegroundColor Yellow
    foreach ($issue in $issues) {
        Write-Host " - $issue" -ForegroundColor Red
    }
    exit 1
}
