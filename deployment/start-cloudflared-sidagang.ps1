$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$cloudflared = 'C:\Program Files (x86)\cloudflared\cloudflared.exe'
$config = Join-Path $projectRoot 'deployment\cloudflared-sidagang.yml'
$runtime = Join-Path $projectRoot 'storage\logs'
$pidFile = Join-Path $runtime 'cloudflared-sidagang-current.pid'

if (-not (Test-Path -LiteralPath $cloudflared)) {
    throw "cloudflared tidak ditemukan di $cloudflared"
}

if (-not (Test-Path -LiteralPath $config)) {
    throw "Konfigurasi tunnel tidak ditemukan di $config"
}

try {
    $metrics = Invoke-WebRequest -Uri 'http://127.0.0.1:21243/metrics' -UseBasicParsing -TimeoutSec 2
    if ($metrics.Content -match '(?m)^cloudflared_tunnel_ha_connections\s+[1-9]') {
        Write-Host 'Cloudflare Tunnel SIDAGANG sudah berjalan.' -ForegroundColor Yellow
        exit 0
    }
} catch {
    # Metrics belum tersedia; tunnel akan dinyalakan di bawah.
}

if (Test-Path -LiteralPath $pidFile) {
    $savedPidText = Get-Content -LiteralPath $pidFile -Raw -ErrorAction SilentlyContinue
    $savedPid = 0
    $hasValidPid = $savedPidText -and [int]::TryParse($savedPidText.Trim(), [ref]$savedPid)
    $savedProcess = if ($hasValidPid) { Get-Process -Id $savedPid -ErrorAction SilentlyContinue } else { $null }
    if ($savedProcess -and $savedProcess.ProcessName -eq 'cloudflared') {
        Write-Host 'Cloudflare Tunnel SIDAGANG sudah berjalan.' -ForegroundColor Yellow
        exit 0
    }
}

& $cloudflared tunnel --config $config ingress validate
if ($LASTEXITCODE -ne 0) {
    throw 'Konfigurasi ingress Cloudflare Tunnel tidak valid.'
}

$process = Start-Process `
    -FilePath $cloudflared `
    -ArgumentList @('tunnel', '--config', $config, 'run') `
    -WorkingDirectory $projectRoot `
    -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $runtime 'cloudflared-sidagang.log') `
    -RedirectStandardError (Join-Path $runtime 'cloudflared-sidagang-error.log') `
    -PassThru

try {
    Set-Content -LiteralPath $pidFile -Value $process.Id -Encoding ascii
} catch {
    Write-Warning "PID cloudflared tidak dapat disimpan: $($_.Exception.Message)"
}
Start-Sleep -Seconds 3

if (-not (Get-Process -Id $process.Id -ErrorAction SilentlyContinue)) {
    throw 'Cloudflare Tunnel gagal berjalan. Periksa storage/logs/cloudflared-sidagang-error.log.'
}

Write-Host 'SIDAGANG aktif di https://sidagang.appcatalog.id' -ForegroundColor Green
