# ==============================================================================
# AUDITOR INTEGRITAS & PENYELARASAN SELURUH SKILL (Hyperion Vault SSoT Engine)
# Memvalidasi YAML frontmatter, broken links, ketersediaan referensi & aturan
# ==============================================================================
param (
    [string]$SkillsDir = (Join-Path (Get-Location).Path ".agents/skills"),
    [string]$RulesDir  = (Join-Path (Get-Location).Path ".agents/rules")
)

$ErrorActionPreference = "SilentlyContinue"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "   AUDITOR INTEGRITAS TATA KELOLA SKILL & ATURAN (Hyperion SSoT)  " -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "Direktori Skill : $SkillsDir" -ForegroundColor Gray
Write-Host "Direktori Aturan: $RulesDir`n" -ForegroundColor Gray

if (-not (Test-Path $SkillsDir)) {
    Write-Host "[ERROR] Direktori skill tidak ditemukan di $SkillsDir" -ForegroundColor Red
    exit 1
}

$SkillFolders = Get-ChildItem -Path $SkillsDir -Directory
$TotalSkills = $SkillFolders.Count
$PassedSkills = 0
$Issues = @()

foreach ($folder in $SkillFolders) {
    $skillName = $folder.Name
    Write-Host "--- Memeriksa Skill: $skillName ---" -ForegroundColor Yellow
    $skillPassed = $true
    
    $skillMd = Join-Path $folder.FullName "SKILL.md"
    if (-not (Test-Path $skillMd)) {
        Write-Host "  [FAIL] Berkas SKILL.md tidak ditemukan!" -ForegroundColor Red
        $Issues += "${skillName}: SKILL.md tidak ditemukan."
        continue
    }
    
    $content = Get-Content $skillMd -Raw
    
    # 1. Validasi YAML Frontmatter
    if ($content -match '^---\s*[\r\n]+([\s\S]*?)[\r\n]+---\s*[\r\n]+') {
        $frontmatter = $matches[1]
        $hasName = $frontmatter -match 'name:\s*([a-zA-Z0-9_-]+)'
        $hasDesc = $frontmatter -match 'description:\s*'
        
        if ($hasName -and $hasDesc) {
            Write-Host "  [PASS] YAML Frontmatter valid (name & description terdefinisi)." -ForegroundColor Green
        } else {
            Write-Host "  [FAIL] YAML Frontmatter tidak lengkap (harus memiliki 'name' dan 'description')." -ForegroundColor Red
            $skillPassed = $false
            $Issues += "${skillName}: YAML frontmatter tidak lengkap."
        }
    } else {
        Write-Host "  [FAIL] Format header YAML frontmatter (---) tidak ditemukan pada baris awal!" -ForegroundColor Red
        $skillPassed = $false
        $Issues += "${skillName}: Tidak memiliki YAML frontmatter pembuka."
    }
    
    # 2. Validasi Link Markdown Lokal (Mendeteksi Broken Links)
    $linkMatches = [regex]::Matches($content, '\[.*?\]\(((\.{1,2}/|[a-zA-Z0-9_-]+/)[^)]+)\)')
    $brokenCount = 0
    
    foreach ($m in $linkMatches) {
        $linkPath = $m.Groups[1].Value
        # Abaikan link http, mailto, atau file://
        if ($linkPath -match '^(http|mailto|file:|#)') { continue }
        
        # Bersihkan query parameter atau anchor #
        $cleanPath = $linkPath -replace '#.*$', ''
        if ([string]::IsNullOrWhiteSpace($cleanPath)) { continue }
        
        $resolvedPath = [System.IO.Path]::GetFullPath((Join-Path $folder.FullName $cleanPath))
        if (-not (Test-Path $resolvedPath)) {
            Write-Host "  [FAIL] Broken Link terdeteksi: $linkPath" -ForegroundColor Red
            Write-Host "         Resolusi Fisik: $resolvedPath" -ForegroundColor DarkGray
            $brokenCount++
            $skillPassed = $false
            $Issues += "${skillName}: Broken link ($linkPath)."
        }
    }
    
    if ($brokenCount -eq 0) {
        Write-Host "  [PASS] Seluruh tautan internal berkas referensi/aturan valid & terhubung." -ForegroundColor Green
    }
    
    # 3. Validasi Keberadaan Subdirektori Penting
    $refDir = Join-Path $folder.FullName "references"
    if (Test-Path $refDir) {
        $refFiles = (Get-ChildItem -Path $refDir -File).Count
        Write-Host "  [INFO] Berkas referensi kanonikal: $refFiles berkas." -ForegroundColor DarkCyan
    }
    
    $scriptsDir = Join-Path $folder.FullName "scripts"
    if (Test-Path $scriptsDir) {
        $scriptFiles = (Get-ChildItem -Path $scriptsDir -File).Count
        Write-Host "  [INFO] Skrip otomasi pendukung: $scriptFiles berkas." -ForegroundColor DarkCyan
    }
    
    if ($skillPassed) {
        $PassedSkills++
        Write-Host "  => Status: INTEGRITAS SEMPURNA (100% Valid)`n" -ForegroundColor Green
    } else {
        Write-Host "  => Status: MEMILIKI MASALAH INTEGRITAS`n" -ForegroundColor Red
    }
}

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "Ringkasan Audit Integritas: $PassedSkills / $TotalSkills Skill Lolos Validasi" -ForegroundColor $(if ($PassedSkills -eq $TotalSkills) { "Green" } else { "Yellow" })
Write-Host "=================================================================" -ForegroundColor Cyan

if ($Issues.Count -gt 0) {
    Write-Host "`nDaftar Masalah Integritas:" -ForegroundColor Red
    foreach ($issue in $Issues) {
        Write-Host " - $issue" -ForegroundColor Red
    }
    exit 1
} else {
    Write-Host "`nSELURUH SKILL 100% SINKRON DAN MEMILIKI INTEGRITAS TINGGI.`n" -ForegroundColor Green
    exit 0
}
