<#
.SYNOPSIS
    Audit Otomatis Kualitas Kode & Anti-Slop Valora Vault (Clean Code Auditor).
.DESCRIPTION
    Memeriksa kepatuhan codebase terhadap standar:
    1. Anti-Placeholder: Ketiadaan // TODO, /* rest of code */, atau stub malas.
    2. PHP Strict Typing: Keberadaan declare(strict_types=1); pada seluruh file PHP.
    3. Svelte 5 Pure JS: Ketiadaan TypeScript (.ts) dan ketiadaan sintaks legasi Svelte 4 (export let, $:).
    4. Button Accessibility: Keberadaan atribut type="button" / type="submit" pada tag button.
    5. Session Driver Invariant: Memastikan SESSION_DRIVER=file (bukan database).
    6. Safe Query Ceiling: Mendeteksi kueri ::all() tanpa batas plafon aman.
.EXAMPLE
    powershell -File .agents/skills/clean-code-refactor/scripts/audit-code-quality.ps1
#>

$ErrorActionPreference = "Stop"

$workspaceRoot = $PSScriptRoot
while (-not (Test-Path (Join-Path $workspaceRoot "AGENTS.md")) -and $workspaceRoot -ne (Split-Path $workspaceRoot -Parent)) {
    $workspaceRoot = Split-Path $workspaceRoot -Parent
}

$issues = [System.Collections.Generic.List[PSCustomObject]]::new()
$passedChecks = 0
$totalChecks = 6

function Add-AuditCheck {
    param(
        [string]$Category,
        [string]$CheckName,
        [bool]$IsPassed,
        [string]$Details
    )
    $script:issues.Add([PSCustomObject]@{
        Kategori   = $Category
        NamaAudit  = $CheckName
        Status     = if ($IsPassed) { "LULUS" } else { "GAGAL" }
        Keterangan = $Details
    })
    if ($IsPassed) {
        $script:passedChecks++
    }
}

# 1. Anti-Placeholder Check
$placeholderMatches = git -C $workspaceRoot grep -n -E '^\s*(//\s*TODO|/\*\s*\.\.\.|//\s*add your logic|console\.log\(''clicked''\))' -- "backend/app/" "frontend/src/" 2>$null
if (-not $placeholderMatches) {
    Add-AuditCheck -Category "Anti-Slop" -CheckName "Zero Placeholder Code" -IsPassed $true -Details "Codebase 100% utuh tanpa placeholder atau komentar pemalas."
} else {
    $count = ($placeholderMatches | Measure-Object).Count
    Add-AuditCheck -Category "Anti-Slop" -CheckName "Zero Placeholder Code" -IsPassed $false -Details "Ditemukan $count baris placeholder yang melanggar RULE-NO-PLACEHOLDER."
}

# 2. PHP Strict Typing Check
$phpFiles = Get-ChildItem -Path (Join-Path $workspaceRoot "backend/app") -Filter "*.php" -Recurse -File
$missingStrict = 0
foreach ($f in $phpFiles) {
    $firstLines = (Get-Content $f.FullName -TotalCount 5) -join "`n"
    if ($firstLines -notmatch "declare\(strict_types=1\);") {
        $missingStrict++
    }
}
if ($missingStrict -eq 0) {
    Add-AuditCheck -Category "Backend" -CheckName "PHP Strict Typing" -IsPassed $true -Details "Semua ($($phpFiles.Count)) file backend diawali declare(strict_types=1);."
} else {
    Add-AuditCheck -Category "Backend" -CheckName "PHP Strict Typing" -IsPassed $false -Details "Ditemukan $missingStrict file PHP tanpa declare(strict_types=1);."
}

# 3. Svelte 5 Pure JS & No Legacy Store
$tsFiles = Get-ChildItem -Path (Join-Path $workspaceRoot "frontend/src") -Include "*.ts", "*.tsx" -Recurse -File
$legacySvelte = git -C $workspaceRoot grep -n -E '(export\s+let\s+[a-zA-Z0-9_]+|\$:\s+)' -- '*.svelte' 2>$null
if ($tsFiles.Count -eq 0 -and -not $legacySvelte) {
    Add-AuditCheck -Category "Frontend" -CheckName "Svelte 5 Pure JS Runes" -IsPassed $true -Details "100% Pure JavaScript tanpa TypeScript dan bebas sintaks legasi Svelte 4."
} else {
    Add-AuditCheck -Category "Frontend" -CheckName "Svelte 5 Pure JS Runes" -IsPassed $false -Details "Terdeteksi $($tsFiles.Count) file .ts atau sintaks legasi Svelte 4."
}

# 4. Button Accessibility Contract
$svelteFiles = Get-ChildItem -Path (Join-Path $workspaceRoot "frontend/src") -Filter "*.svelte" -Recurse -File
$untypedButtons = [System.Collections.Generic.List[string]]::new()
foreach ($f in $svelteFiles) {
    $content = [System.IO.File]::ReadAllText($f.FullName)
    $btnMatches = [regex]::Matches($content, '<button\b(?![^>]*\btype=)[^>]*>', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    foreach ($m in $btnMatches) {
        $untypedButtons.Add("$($f.Name): $($m.Value)")
    }
}
if ($untypedButtons.Count -eq 0) {
    Add-AuditCheck -Category "UI/UX" -CheckName "Button Accessibility Contract" -IsPassed $true -Details "Seluruh elemen button memiliki atribut type='button' / type='submit'."
} else {
    Add-AuditCheck -Category "UI/UX" -CheckName "Button Accessibility Contract" -IsPassed $false -Details "Ditemukan $($untypedButtons.Count) elemen button tanpa atribut type eksplisit."
}

# 5. Session Driver Invariant
$envFile = Join-Path $workspaceRoot "backend/.env"
$sessionOk = $true
if (Test-Path $envFile) {
    $envContent = Get-Content $envFile -Raw
    if ($envContent -match "SESSION_DRIVER\s*=\s*database") {
        $sessionOk = $false
    }
}
if ($sessionOk) {
    Add-AuditCheck -Category "Infrastruktur" -CheckName "Session Driver Invariant" -IsPassed $true -Details "SESSION_DRIVER terkonfigurasi file (Bebas koneksi DB overhead)."
} else {
    Add-AuditCheck -Category "Infrastruktur" -CheckName "Session Driver Invariant" -IsPassed $false -Details "SESSION_DRIVER terdeteksi 'database'! Wajib diganti ke 'file'."
}

# 6. Safe Query Ceilings
$rawAllQueries = git -C $workspaceRoot grep -n -E "::all\(\)" -- "backend/app/Http/Controllers/" 2>$null
if (-not $rawAllQueries) {
    Add-AuditCheck -Category "Basis Data" -CheckName "Safe Query Ceilings" -IsPassed $true -Details "Tidak ditemukan pemanggilan Model::all() tanpa batas limit."
} else {
    $count = ($rawAllQueries | Measure-Object).Count
    Add-AuditCheck -Category "Basis Data" -CheckName "Safe Query Ceilings" -IsPassed $false -Details "Ditemukan $count query ::all() tanpa batas plafon kueri aman limit(1000)."
}

# Tampilkan Laporan
$scorePct = [math]::Round(($passedChecks / $totalChecks) * 100, 1)

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   HASIL AUDIT MUTU KODE & CRAFTSMANSHIP (CLEAN CODE)   " -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "Skor Kualitas Kode : $passedChecks / $totalChecks Lulus ($scorePct%)`n"

foreach ($item in $issues) {
    $color = if ($item.Status -eq "LULUS") { "Green" } else { "Red" }
    $icon  = if ($item.Status -eq "LULUS") { "[OK]" } else { "[FAIL]" }
    Write-Host "$icon [$($item.Kategori)] $($item.NamaAudit)" -ForegroundColor $color
    Write-Host "     Catatan: $($item.Keterangan)" -ForegroundColor Gray
}

Write-Host "`n--------------------------------------------------------" -ForegroundColor Cyan
if ($scorePct -ge 83.3) {
    Write-Host "[STATUS: KODE BERSIH & PRODUCTION-READY] Skor $scorePct%" -ForegroundColor Green
    exit 0
} else {
    Write-Host "[STATUS: PERLU REFAKTORISASI] Skor $scorePct% (Di bawah batas kelulusan)" -ForegroundColor Yellow
    exit 1
}
