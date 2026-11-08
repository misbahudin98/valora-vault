<#
.SYNOPSIS
    Generator data dimensi kalender akademik (dim_kalender_akademik) untuk 10 tahun (2020 - 2030).
.DESCRIPTION
    Menghitung tanggal Gregorian, nama hari Indonesia, minggu ISO-8601, Tahun Ajaran (Juli-Juni),
    semester akademik (1 ganjil / 2 genap), dan kohort 5-tahunan.
.PARAMETER OutputSqlFile
    Path ke berkas SQL keluaran (default: dim_kalender_akademik_seed.sql).
#>
param(
    [string]$OutputSqlFile = "dim_kalender_akademik_seed.sql"
)

$ErrorActionPreference = "Stop"

$startDate = [DateTime]::Parse("2020-01-01")
$endDate   = [DateTime]::Parse("2030-12-31")

$dayNamesIndo = @{
    "Sunday"    = "Minggu"
    "Monday"    = "Senin"
    "Tuesday"   = "Selasa"
    "Wednesday" = "Rabu"
    "Thursday"  = "Kamis"
    "Friday"    = "Jumat"
    "Saturday"  = "Sabtu"
}

$sb = [System.Text.StringBuilder]::new()
[void]$sb.AppendLine("-- Seed Data: dim_kalender_akademik (2020 - 2030)")
[void]$sb.AppendLine("BEGIN TRANSACTION;")

$currentDate = $startDate
$count = 0

while ($currentDate -le $endDate) {
    $dateStr = $currentDate.ToString("yyyy-MM-dd")
    $year = $currentDate.Year
    $month = $currentDate.Month
    $dayName = $dayNamesIndo[$currentDate.DayOfWeek.ToString()]
    
    # ISO-8601 Week Number
    $culture = [System.Globalization.CultureInfo]::InvariantCulture
    $week = $culture.Calendar.GetWeekOfYear($currentDate, [System.Globalization.CalendarWeekRule]::FirstFourDayWeek, [DayOfWeek]::Monday)

    # Tahun Ajaran: Juli N s/d Juni N+1
    if ($month -ge 7) {
        $academicYear = "$year/$($year + 1)"
        $semester = 1 # Ganjil
    } else {
        $academicYear = "$($year - 1)/$year"
        $semester = 2 # Genap
    }

    # 5-Year Cohort
    $cohortBase = [Math]::Floor($year / 5) * 5
    $cohort = "$cohortBase-$($cohortBase + 4)"

    $isWeekend = if ($currentDate.DayOfWeek -in @([DayOfWeek]::Saturday, [DayOfWeek]::Sunday)) { 1 } else { 0 }

    $sql = "INSERT OR IGNORE INTO dim_kalender_akademik (tanggal, tahun_masehi, bulan_masehi, minggu_masehi, hari_indonesia, tahun_ajaran, semester_akademik, pekan_efektif_kbm, is_hari_libur, five_year_cohort) VALUES ('$dateStr', $year, $month, $week, '$dayName', '$academicYear', $semester, 0, $isWeekend, '$cohort');"
    [void]$sb.AppendLine($sql)

    $currentDate = $currentDate.AddDays(1)
    $count++
}

[void]$sb.AppendLine("COMMIT;")
[System.IO.File]::WriteAllText($OutputSqlFile, $sb.ToString(), [System.Text.Encoding]::UTF8)

Write-Host "Berhasil men-generate $count baris dimensi tanggal ke berkas: $OutputSqlFile" -ForegroundColor Green
