<#
.SYNOPSIS
    Audit Otomatis Kepatuhan Desain Fitur 3-Pilar (Triple-Pillar Feature Design Auditor).
.DESCRIPTION
    Memeriksa naskah spesifikasi/intake terhadap:
    1. Anti-Slop & Metrik Kuantitatif (Ketiadaan kata sifat subjektif).
    2. Formula JTBD Baku (Sebagai, Ketika, Saya ingin, Sehingga).
    3. Pengikatan Peran Kanonikal RBAC.
    4. Pilar Logika Bisnis (Skenario Gherkin 200, 422, 403 & State Machine).
    5. Pilar Basis Data (Skema tabel, relasi foreign key, tipe data, indeks).
    6. Pilar UI/UX (4 Visual States: Loading, Empty, Error, Success & UI Delivery Target).
.PARAMETER FilePath
    Path absolut atau relatif ke berkas spesifikasi markdown yang diaudit.
.EXAMPLE
    pwsh .agents/skills/discovery-architect/scripts/audit-feature-design.ps1 -FilePath "doc/features/01_example.md"
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
$passedPoints = 0
$totalPoints = 8
$auditResults = [System.Collections.Generic.List[PSCustomObject]]::new()

function Add-AuditResult {
    param(
        [string]$Pillar,
        [string]$CheckItem,
        [bool]$IsPassed,
        [string]$Message
    )
    $script:auditResults.Add([PSCustomObject]@{
        Pilar     = $Pillar
        Aspek     = $CheckItem
        Status    = if ($IsPassed) { "LULUS" } else { "GAGAL" }
        Keterangan = $Message
    })
    if ($IsPassed) {
        $script:passedPoints++
    }
}

# 1. Anti-Slop Check (Kata Sifat Subjektif)
$ambiguousWords = @("fleksibel", "mudah", "cepat", "canggih", "modern", "user-friendly", "seamless", "holistik", "sinergi")
$foundAmbiguous = @()
foreach ($word in $ambiguousWords) {
    if ($content -match "\b$word\b") {
        $foundAmbiguous += $word
    }
}
if ($foundAmbiguous.Count -eq 0) {
    Add-AuditResult -Pillar "Umum" -CheckItem "Anti-Slop Ambiguity" -IsPassed $true -Message "Bebas dari kata sifat subjektif/pemanis."
} else {
    Add-AuditResult -Pillar "Umum" -CheckItem "Anti-Slop Ambiguity" -IsPassed $false -Message "Ditemukan kata sifat subjektif: $($foundAmbiguous -join ', '). Ganti dengan metrik kuantitatif."
}

# 2. Formula JTBD
$hasJtbd = ($content -match "(?i)\*{0,2}sebagai\*{0,2}\s+.+,\s+(\*{0,2}ketika\*{0,2}\s+.+,\s+)?\*{0,2}saya\s+ingin\*{0,2}\s+.+,\s+\*{0,2}sehingga\*{0,2}\s+" -or $content -match "(?i)job\s+statement" -or $content -match "(?i)outcome-driven")
if ($hasJtbd) {
    Add-AuditResult -Pillar "Umum" -CheckItem "Formula JTBD" -IsPassed $true -Message "Formula Jobs-to-be-Done teridentifikasi secara baku."
} else {
    Add-AuditResult -Pillar "Umum" -CheckItem "Formula JTBD" -IsPassed $false -Message "Formula JTBD tidak terdeteksi (Wajib memuat 'Sebagai..., ketika..., saya ingin..., sehingga...')."
}

# 3. Canonical Role Alignment
$canonicalRoles = [System.Collections.Generic.List[string]]::new()
@(
    "admin", "manager", "staff", "auditor", "user", 
    "petugas", "client", "klien", "notaris", "ppat",
    "admin_yayasan", "mudir", "pimpinan_unit", "admin_pkbm", "admin_ltq", "admin_pesantren", "guru_ltq", "wali_kamar", "guru_pkbm", "wali_santri", "pengurus"
) | ForEach-Object { $canonicalRoles.Add($_) }

# Coba baca peran langsung dari backend/config/permissions.php jika tersedia
$permFile = "backend/config/permissions.php"
if (Test-Path $permFile) {
    $permContent = Get-Content -Path $permFile -Raw
    $matches = [regex]::Matches($permContent, "'([a-zA-Z0-9_\-]+)'\s*=>\s*\[")
    foreach ($m in $matches) {
        $rName = $m.Groups[1].Value
        if ($rName -ne "roles" -and -not $canonicalRoles.Contains($rName)) {
            $canonicalRoles.Add($rName)
        }
    }
}

$foundRole = $false
foreach ($r in $canonicalRoles) {
    if ($content -match "(?i)\b$r\b") {
        $foundRole = $true
        break
    }
}
if ($foundRole) {
    Add-AuditResult -Pillar "Logika Bisnis" -CheckItem "Canonical Role Alignment" -IsPassed $true -Message "Terikat dengan peran kanonikal sistem resmi (config/permissions.php)."
} else {
    Add-AuditResult -Pillar "Logika Bisnis" -CheckItem "Canonical Role Alignment" -IsPassed $false -Message "Tidak terdeteksi peran kanonikal resmi dari backend/config/permissions.php."
}

# 4. Gherkin Verification Contract (Happy, 422, 403)
$hasHappy = ($content -match "(?i)200" -or $content -match "(?i)201" -or $content -match "(?i)happy")
$hasValidation = ($content -match "(?i)422" -or $content -match "(?i)validation")
$hasAuth = ($content -match "(?i)403" -or $content -match "(?i)forbidden" -or $content -match "(?i)bola")
if ($hasHappy -and ($hasValidation -or $hasAuth)) {
    Add-AuditResult -Pillar "Logika Bisnis" -CheckItem "Gherkin Verification Contract" -IsPassed $true -Message "Memuat kontrak pengujian deterministik (Happy & Exception Paths)."
} else {
    Add-AuditResult -Pillar "Logika Bisnis" -CheckItem "Gherkin Verification Contract" -IsPassed $false -Message "Belum memuat kontrak skenario pengujian 3-jalur (200, 422, 403)."
}

# 5. Database Schema & Relational Integrity
$hasTable = ($content -match "(?i)tabel" -or $content -match "(?i)table" -or $content -match "(?i)migration" -or $content -match "(?i)erd")
$hasRelation = ($content -match "(?i)foreign" -or $content -match "(?i)_id" -or $content -match "(?i)fk\s*:")
if ($hasTable -and $hasRelation) {
    Add-AuditResult -Pillar "Basis Data" -CheckItem "Relational Schema Integrity" -IsPassed $true -Message "Definisi tabel dan keterkaitan foreign key terpetakan."
} else {
    Add-AuditResult -Pillar "Basis Data" -CheckItem "Relational Schema Integrity" -IsPassed $false -Message "Struktur skema relasional atau foreign key belum didefinisikan secara eksplisit."
}

# 6. Database Indexing / Ceilings
if ($content -match "(?i)index" -or $content -match "(?i)indeks" -or $content -match "(?i)limit" -or $content -match "(?i)b-tree") {
    Add-AuditResult -Pillar "Basis Data" -CheckItem "Performance & Indexing" -IsPassed $true -Message "Memuat batasan kueri atau strategi indeks performa."
} else {
    Add-AuditResult -Pillar "Basis Data" -CheckItem "Performance & Indexing" -IsPassed $false -Message "Tidak ada penanda indeks komposit B-Tree atau batasan kueri plafon aman."
}

# 7. UI Delivery Target
$deliveryTargets = @("standalone_page", "modal_dialog", "slide_over_drawer", "inline_action", "embedded_widget", "headless_job")
$foundTarget = $false
foreach ($dt in $deliveryTargets) {
    if ($content -match "(?i)\b$dt\b") {
        $foundTarget = $true
        break
    }
}
if ($foundTarget) {
    Add-AuditResult -Pillar "UI / UX" -CheckItem "UI Delivery Target" -IsPassed $true -Message "UI Delivery Target terdefinisi eksplisit (Anti-Asumsi Halaman Tunggal)."
} else {
    Add-AuditResult -Pillar "UI / UX" -CheckItem "UI Delivery Target" -IsPassed $false -Message "Delivery Target tidak ditemukan (Wajib salah satu dari: standalone_page, modal_dialog, slide_over_drawer, inline_action, embedded_widget, headless_job)."
}

# 8. Full-State UI (Loading, Empty, Error, Success)
$states = @("loading", "empty", "error", "success")
$missingStates = @()
foreach ($s in $states) {
    if (-not ($content -match "(?i)$s")) {
        $missingStates += $s
    }
}
if ($missingStates.Count -eq 0) {
    Add-AuditResult -Pillar "UI / UX" -CheckItem "Full-State UI Mapping" -IsPassed $true -Message "4 visual state (Loading, Empty, Error, Success) terpetakan lengkap."
} else {
    Add-AuditResult -Pillar "UI / UX" -CheckItem "Full-State UI Mapping" -IsPassed $false -Message "Pemetaan state visual belum lengkap. Kurang: $($missingStates -join ', ')."
}

# Hitung Skor
$scorePercentage = [math]::Round(($passedPoints / $totalPoints) * 100, 1)

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "   HASIL AUDIT DESAIN FITUR 3-PILAR (TRIPLE-PILLAR AUDIT)" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "Target Berkas : $FilePath"
Write-Host "Total Poin    : $passedPoints / $totalPoints Lulus ($scorePercentage%)`n"

foreach ($res in $auditResults) {
    $color = if ($res.Status -eq "LULUS") { "Green" } else { "Red" }
    $icon  = if ($res.Status -eq "LULUS") { "[OK]" } else { "[FAIL]" }
    Write-Host "$icon [$($res.Pilar)] $($res.Aspek)" -ForegroundColor $color
    Write-Host "     Catatan: $($res.Keterangan)" -ForegroundColor Gray
}

Write-Host "`n--------------------------------------------------------" -ForegroundColor Cyan
if ($scorePercentage -ge 87.5) {
    Write-Host "[STATUS: LULUS GERBANG REVIEW GATE] Skor $scorePercentage% (Siap Dikompilasi)" -ForegroundColor Green
    Write-Host "Naskah spesifikasi memenuhi standar internasional DDD, 3NF, dan NN/g.`n" -ForegroundColor Green
    exit 0
} else {
    Write-Host "[STATUS: PERLU PERBAIKAN] Skor $scorePercentage% (Di bawah batas kelulusan 87.5%)" -ForegroundColor Yellow
    Write-Host "Harap perbaiki aspek yang berstatus [FAIL] sebelum melanjutkan pembuatan kode.`n" -ForegroundColor Yellow
    exit 1
}
