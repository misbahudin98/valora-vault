<#
.SYNOPSIS
    International Prompt Auditor & Quality Scorer (PowerShell v7+)
.DESCRIPTION
    Melakukan audit otomatis terhadap teks prompt berdasarkan 6 Dimensi Kualitas Internasional
    dan 7 Invariant SDD-Enterprise-Deterministic.
.PARAMETER PromptFile
    Path ke berkas prompt teks yang ingin diaudit.
.PARAMETER PromptText
    Teks prompt langsung sebagai string.
.EXAMPLE
    .\audit-prompt.ps1 -PromptFile "my_prompt.txt"
    .\audit-prompt.ps1 -PromptText "Buatkan saya auth system di Laravel..."
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $false)]
    [string]$PromptFile,

    [Parameter(Mandatory = $false)]
    [string]$PromptText
)

# 1. Resolve Input Text
if ([string]::IsNullOrWhiteSpace($PromptText)) {
    if ([string]::IsNullOrWhiteSpace($PromptFile) -or -not (Test-Path $PromptFile)) {
        Write-Error "Harap sediakan -PromptFile yang valid atau berikan -PromptText."
        exit 1
    }
    $PromptText = Get-Content -Path $PromptFile -Raw -Encoding UTF8
}

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "   INTERNATIONAL PROMPT ENGINEERING AUDITOR & QUALITY SCORER     " -ForegroundColor Yellow
Write-Host "=================================================================" -ForegroundColor Cyan

$scoreDim1 = 0 # Role & Identity (15)
$scoreDim2 = 0 # Context Grounding & SSoT (20)
$scoreDim3 = 0 # Scope Granularity & Anti-Bulk (15)
$scoreDim4 = 0 # Constraint Hardening & Anti-Slop (20)
$scoreDim5 = 0 # Output Schema & Determinism (15)
$scoreDim6 = 0 # Verification & Review Gate (15)

$findings = [System.Collections.Generic.List[string]]::new()
$smells = [System.Collections.Generic.List[string]]::new()

# --- DIMENSI 1: Role & Identity Calibration (Max 15) ---
if ($PromptText -match '(?i)(<role>|kamu adalah|sebagai|role:|architect|senior engineer|per coding style)') {
    $scoreDim1 += 8
    if ($PromptText -match '(?i)(standard|per coding style 2\.0|owasp|enterprise|defensive|spesialisasi)') {
        $scoreDim1 += 7
    }
} else {
    $findings.Add("[Dimensi 1] Role kognitif tidak didefinisikan secara presisi.")
}

# --- DIMENSI 2: Context Grounding & SSoT (Max 20) ---
if ($PromptText -match '(?i)(<context>|lingkungan|ssot|single source of truth|workspace|doc/|architecture\.md|llms\.txt)') {
    $scoreDim2 += 10
}
if ($PromptText -match '(?i)(laravel 13|php 8\.[34]|sqlite|wal|svelte 5|tailwind|session_driver)') {
    $scoreDim2 += 10
} else {
    $findings.Add("[Dimensi 2] Batasan stack atau file referensi SSoT kurang eksplisit.")
}

# --- DIMENSI 3: Scope Granularity & Anti-Bulk (Max 15) ---
$isBulk = $false
if ($PromptText -match '(?i)(semua fitur|seluruh modul|buatkan lengkap auth.+crud.+dashboard.+landing|all roles)') {
    $isBulk = $true
    $smells.Add("[SMELL] Terdeteksi Bulk Generation request yang berpotensi menyebabkan context drift dan code slop.")
}

if ($PromptText -match '(?i)(single feature|1 fitur|satu fitur|1 role|granular|unit kerja)') {
    $scoreDim3 += 15
} elseif (-not $isBulk) {
    $scoreDim3 += 8
}

# --- DIMENSI 4: Constraint Hardening & Anti-Slop (Max 20) ---
if ($PromptText -match '(?i)(no_placeholder|dilarang keras|dilarang menggunakan \/\/\s*todo|100% utuh|production-ready)') {
    $scoreDim4 += 8
} else {
    $findings.Add("[Dimensi 4] Tidak ada klausul tegas 'NO_PLACEHOLDER'. Model rentan menulis kode rintisan / TODO.")
}

if ($PromptText -match '(?i)(loading|empty|error|success|4 state|4 visual states)') {
    $scoreDim4 += 6
} else {
    $findings.Add("[Dimensi 4] Mandat 4 Visual State antarmuka (Loading, Empty, Error, Success) tidak disebutkan.")
}

if ($PromptText -match '(?i)(db::transaction|declare\(strict_types=1\)|atomic|rfc 7807|parameter binding)') {
    $scoreDim4 += 6
}

# --- DIMENSI 5: Output Schema & Determinism (Max 15) ---
if ($PromptText -match '(?i)(<output_contract>|<output_format>|format keluaran|urutan berkas|json schema|gherkin)') {
    $scoreDim5 += 8
}
if ($PromptText -match '(?i)(\.php|\.svelte|\.json|routes/|tests/Feature/|app/Http/)') {
    $scoreDim5 += 7
} else {
    $findings.Add("[Dimensi 5] Target file path atau skema output kurang terdefinisi secara terstruktur.")
}

# --- DIMENSI 6: Verification & Review Gate (Max 15) ---
if ($PromptText -match '(?i)(test|phpunit|pest|feature test|200|422|403|npm run build|linter)') {
    $scoreDim6 += 8
}
if ($PromptText -match '(?i)(review gate|approval|langkah a|langkah b|human-in-the-loop|konfirmasi pengguna)') {
    $scoreDim6 += 7
} else {
    $findings.Add("[Dimensi 6] Tidak ada protokol persetujuan resmi (Review Gate / Human-in-the-Loop checkpoint).")
}

# Check for Slop Smells
if ($PromptText -match '(?i)(buat yang bagus|sebagus mungkin|secepat mungkin|yang keren|make it modern)') {
    $smells.Add("[SMELL] Terdapat kata sifat subjektif tanpa metrik teknis terukur (Vague Superlatives).")
}
if ($PromptText -match '(?i)(typescript|\.ts\b)') {
    if ($PromptText -notmatch '(?i)dilarang\s+(keras\s+)?(menggunakan\s+)?(typescript|\.ts)') {
        $smells.Add("[SMELL] Terdeteksi TypeScript padahal standar proyek Hyperion adalah 100% Pure JavaScript ES2024+.")
    }
}

# Calculate Total
$totalScore = $scoreDim1 + $scoreDim2 + $scoreDim3 + $scoreDim4 + $scoreDim5 + $scoreDim6

$tier = "Tier 4: Naive / Slop-Prone (KRITIS)"
$tierColor = "Red"
if ($totalScore -ge 90) {
    $tier = "Tier 1: Deterministic Mission-Critical (STANDAR EMAS)"
    $tierColor = "Green"
} elseif ($totalScore -ge 71) {
    $tier = "Tier 2: Enterprise Compliant (BAIK)"
    $tierColor = "Cyan"
} elseif ($totalScore -ge 46) {
    $tier = "Tier 3: Functional Basic (RENTAN)"
    $tierColor = "Yellow"
}

Write-Host "HASIL AUDIT METRIK:" -ForegroundColor White
Write-Host "  1. Role & Identity Calibration : $scoreDim1 / 15"
Write-Host "  2. Context Grounding & SSoT    : $scoreDim2 / 20"
Write-Host "  3. Scope Granularity & Anti-Bulk: $scoreDim3 / 15"
Write-Host "  4. Constraint Hardening & Anti-Slop: $scoreDim4 / 20"
Write-Host "  5. Output Schema & Determinism : $scoreDim5 / 15"
Write-Host "  6. Verification & Review Gate  : $scoreDim6 / 15"
Write-Host "-----------------------------------------------------------------"
Write-Host "TOTAL SKOR KUALITAS : $totalScore / 100" -ForegroundColor $tierColor
Write-Host "STATUS TIER         : $tier" -ForegroundColor $tierColor
Write-Host "-----------------------------------------------------------------"

if ($smells.Count -gt 0) {
    Write-Host "`nPROMPT SMELLS / WARNINGS DITEMUKAN:" -ForegroundColor Red
    foreach ($s in $smells) {
        Write-Host "  ! $s" -ForegroundColor Yellow
    }
}

if ($findings.Count -gt 0) {
    Write-Host "`nREKOMENDASI PERBAIKAN STRUKTURAL:" -ForegroundColor Magenta
    foreach ($f in $findings) {
        Write-Host "  - $f" -ForegroundColor White
    }
} else {
    Write-Host "`nSELAMAT: Prompt ini telah mematuhi seluruh protokol deterministik internasional!" -ForegroundColor Green
}
Write-Host "=================================================================`n"
