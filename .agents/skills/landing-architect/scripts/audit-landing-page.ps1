<#
.SYNOPSIS
    Audits a Landing Page file (Svelte, HTML, PHP, MD) against Hyperion Vault 7 Golden Invariants.
.DESCRIPTION
    Verifies Core Web Vitals readiness, SEO/AEO/GEO/AIO signals, schema.org presence,
    accessibility standards, motion design compliance, and anti-slop rules.
.PARAMETER FilePath
    Path to the landing page file to audit.
.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .agents/skills/landing-architect/scripts/audit-landing-page.ps1 -FilePath "frontend/src/routes/+page.svelte"
#>

[CmdletBinding()]
param (
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$FilePath
)

if (-not (Test-Path $FilePath)) {
    Write-Error "File not found: $FilePath"
    exit 1
}

$content = Get-Content -Path $FilePath -Raw
$fileName = Split-Path -Leaf $FilePath

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  HYPERION VAULT: LANDING PAGE & GROWTH AUDIT ENGINE      " -ForegroundColor Cyan
Write-Host "  Target File: $fileName                                  " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$score = 0
$totalCheckpoints = 10
$checks = @()

# Checkpoint 1: Single H1 Tag Check
$h1Matches = [regex]::Matches($content, "<h1[^>]*>.*?</h1>", [System.Text.RegularExpressions.RegexOptions]::Singleline)
if ($h1Matches.Count -eq 1) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "1. Single H1 Tag (SEO)"; Status = "PASS"; Weight = 10; Notes = "Tepat 1 tag <h1> terdeteksi." }
} elseif ($h1Matches.Count -eq 0) {
    $checks += [PSCustomObject]@{ Checkpoint = "1. Single H1 Tag (SEO)"; Status = "FAIL"; Weight = 0; Notes = "Tidak ditemukan tag <h1> utama." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "1. Single H1 Tag (SEO)"; Status = "FAIL"; Weight = 0; Notes = "Ditemukan $($h1Matches.Count) tag <h1> (Hanya boleh 1 per landing page)." }
}

# Checkpoint 2: Semantic HTML Structure (header, main, section, footer)
$hasMain = $content -match "<main[^>]*>"
$hasSection = $content -match "<section[^>]*>"
$hasHeaderOrNav = ($content -match "<header[^>]*>") -or ($content -match "<nav[^>]*>")
if ($hasMain -and $hasSection -and $hasHeaderOrNav) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "2. Semantic HTML5 Structure"; Status = "PASS"; Weight = 10; Notes = "Elemen semantik (<header/nav>, <main>, <section>) lengkap." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "2. Semantic HTML5 Structure"; Status = "WARN"; Weight = 5; Notes = "Kurang elemen semantik (wajib memisahkan header, main, dan section)." }
    $score += 5
}

# Checkpoint 3: Image Core Web Vitals (alt, width/height or aspect ratio)
$imgTags = [regex]::Matches($content, "<img[^>]+>")
$imgViolations = 0
foreach ($img in $imgTags) {
    $str = $img.Value
    if (-not ($str -match 'alt=') -or -not ($str -match 'width=' -or $str -match 'height=' -or $str -match 'aspect-')) {
        $imgViolations++
    }
}
if ($imgTags.Count -eq 0) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "3. Image CWV Optimization"; Status = "PASS"; Weight = 10; Notes = "Tidak ada tag <img> mentah tanpa dimensi (atau memakai SVG/CSS)." }
} elseif ($imgViolations -eq 0) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "3. Image CWV Optimization"; Status = "PASS"; Weight = 10; Notes = "Seluruh $($imgTags.Count) gambar memiliki atribut alt dan reservasi dimensi." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "3. Image CWV Optimization"; Status = "FAIL"; Weight = 0; Notes = "$imgViolations gambar tidak memiliki atribut alt atau dimensi eksplisit (ancaman CLS)." }
}

# Checkpoint 4: JSON-LD Schema.org Detection (AEO & AIO)
$hasSchema = ($content -match 'application/ld\+json') -or ($content -match 'schema\.org')
if ($hasSchema) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "4. Schema.org JSON-LD (AEO/AIO)"; Status = "PASS"; Weight = 10; Notes = "Skema terstruktur JSON-LD ditemukan." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "4. Schema.org JSON-LD (AEO/AIO)"; Status = "WARN"; Weight = 0; Notes = "Tidak ditemukan script schema.org JSON-LD (FAQPage/Product)." }
}

# Checkpoint 5: GEO Statistical & Citation Density
$hasPercentage = $content -match '\d+(\.\d+)?%'
$hasNumericMetric = $content -match '\b\d{2,}\b'
if ($hasPercentage -and $hasNumericMetric) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "5. GEO Evidence & Stats Density"; Status = "PASS"; Weight = 10; Notes = "Memuat data numerik dan persentase konkret untuk sitasi LLM." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "5. GEO Evidence & Stats Density"; Status = "WARN"; Weight = 5; Notes = "Kurang data statistik konkret/angka numerik terverifikasi." }
    $score += 5
}

# Checkpoint 6: Anti-Slop (No Placeholder Check)
$slopPatterns = @("lorem ipsum", "// TODO", "/* TODO", "implement later", "add copy here", "teks judul di sini", "deskripsi produk di sini")
$slopFound = @()
foreach ($pat in $slopPatterns) {
    if ($content -match [regex]::Escape($pat)) {
        $slopFound += $pat
    }
}
if ($slopFound.Count -eq 0) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "6. Anti-Slop (No Placeholders)"; Status = "PASS"; Weight = 10; Notes = "100% bebas dari teks malas dan placeholder generik." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "6. Anti-Slop (No Placeholders)"; Status = "FAIL"; Weight = 0; Notes = "Terdeteksi teks rintisan pemalas: $($slopFound -join ', ')" }
}

# Checkpoint 7: Motion Accessibility (prefers-reduced-motion)
$hasMotion = ($content -match 'gsap') -or ($content -match '@keyframes') -or ($content -match 'animate-') -or ($content -match 'transition:')
if ($hasMotion) {
    $hasReducedMotion = ($content -match 'prefers-reduced-motion')
    if ($hasReducedMotion) {
        $score += 10
        $checks += [PSCustomObject]@{ Checkpoint = "7. Motion Accessibility (WCAG 2.2)"; Status = "PASS"; Weight = 10; Notes = "Animasi menyertakan pelindung prefers-reduced-motion." }
    } else {
        $checks += [PSCustomObject]@{ Checkpoint = "7. Motion Accessibility (WCAG 2.2)"; Status = "FAIL"; Weight = 0; Notes = "Animasi terdeteksi tanpa penanganan prefers-reduced-motion!" }
    }
} else {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "7. Motion Accessibility (WCAG 2.2)"; Status = "PASS"; Weight = 10; Notes = "Tidak menggunakan animasi berat yang membahayakan vestibular." }
}

# Checkpoint 8: Button Accessibility (type attribute)
$buttons = [regex]::Matches($content, "<button[^>]*>")
$btnViolations = 0
foreach ($btn in $buttons) {
    if (-not ($btn.Value -match 'type=')) {
        $btnViolations++
    }
}
if ($btnViolations -eq 0) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "8. Button Accessibility (type=)"; Status = "PASS"; Weight = 10; Notes = "Seluruh elemen <button> menyertakan tipe eksplisit." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "8. Button Accessibility (type=)"; Status = "FAIL"; Weight = 0; Notes = "$btnViolations tag <button> tidak memiliki atribut type='button'/'submit'." }
}

# Checkpoint 9: StoryBrand & Conversion Anatomy (FAQ or Comparison)
$hasFAQ = ($content -match 'faq' -or $content -match 'pertanyaan' -or $content -match 'tanya')
$hasCTA = ($content -match 'cta' -or $content -match 'daftar' -or $content -match 'mulai' -or $content -match 'demo' -or $content -match 'hubungi')
if ($hasFAQ -and $hasCTA) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "9. Conversion Anatomy (CTA & FAQ)"; Status = "PASS"; Weight = 10; Notes = "Struktur CTA dan penanganan keraguan (FAQ) terdeteksi." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "9. Conversion Anatomy (CTA & FAQ)"; Status = "WARN"; Weight = 5; Notes = "Komponen mitigasi friksi (FAQ) atau CTA penutup belum lengkap." }
    $score += 5
}

# Checkpoint 10: Mobile Touch Target & Compact Density
$hasMobileClasses = ($content -match 'sm:' -or $content -match 'md:' -or $content -match 'lg:')
if ($hasMobileClasses) {
    $score += 10
    $checks += [PSCustomObject]@{ Checkpoint = "10. Mobile Responsiveness"; Status = "PASS"; Weight = 10; Notes = "Menerapkan breakpoint responsive Tailwind secara proporsional." }
} else {
    $checks += [PSCustomObject]@{ Checkpoint = "10. Mobile Responsiveness"; Status = "WARN"; Weight = 5; Notes = "Minim indikator responsivitas multi-device." }
    $score += 5
}

# Output Report Table
$checks | Format-Table Checkpoint, Status, Weight, Notes -AutoSize

Write-Host "----------------------------------------------------------" -ForegroundColor Gray
Write-Host "Total Score: $score / 100" -ForegroundColor $(if ($score -ge 85) { "Green" } elseif ($score -ge 65) { "Yellow" } else { "Red" })

if ($score -ge 85) {
    Write-Host "STATUS: READY FOR PRODUCTION (Mematuhi Standar Emas Landing Page)" -ForegroundColor Green
    exit 0
} else {
    Write-Host "STATUS: NEEDS REVISION (Perbaiki catatan yang ditandai FAIL/WARN)" -ForegroundColor Red
    exit 1
}
