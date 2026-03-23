param(
    [string]$Ref = 'HEAD',
    [string]$OutputDir = (Join-Path $PSScriptRoot '..\dist'),
    [string]$ArchiveName = 'beratungsassistent-vps.zip'
)

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$archivePath = Join-Path $OutputDir $ArchiveName
if (Test-Path $archivePath) {
    Remove-Item -Path $archivePath -Force
}

& git -C $repoRoot archive --format=zip "--prefix=Beratungsassistent/" "--output=$archivePath" $Ref
if ($LASTEXITCODE -ne 0) {
    throw 'Das VPS-Paket konnte nicht erstellt werden.'
}

Write-Output $archivePath
