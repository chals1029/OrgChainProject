# OrgChain local demo tunnel (laptop must stay ON)
# Usage:
#   .\scripts\start-demo-tunnel.ps1
#   .\scripts\start-demo-tunnel.ps1 -Port 8000
#   .\scripts\start-demo-tunnel.ps1 -Tool ngrok   # if ngrok is installed

param(
  [int]$Port = 8000,
  [ValidateSet('cloudflare', 'ngrok')]
  [string]$Tool = 'cloudflare'
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

Write-Host ""
Write-Host "OrgChain demo tunnel" -ForegroundColor Cyan
Write-Host "Laptop must stay ON + awake. Close this window to stop." -ForegroundColor Yellow
Write-Host ""

# 1) Ensure PHP app is listening
$listening = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if (-not $listening) {
  Write-Host "Starting php artisan serve on :$Port ..."
  Start-Process -FilePath "php" -ArgumentList @("artisan", "serve", "--host=127.0.0.1", "--port=$Port") -WorkingDirectory $Root -WindowStyle Minimized
  Start-Sleep -Seconds 2
} else {
  Write-Host "App already listening on :$Port"
}

# Warm check
try {
  Invoke-WebRequest -Uri "http://127.0.0.1:$Port" -UseBasicParsing -TimeoutSec 5 | Out-Null
  Write-Host "Local app OK: http://127.0.0.1:$Port"
} catch {
  Write-Host "WARN: local app not responding yet — tunnel may still work after serve boots." -ForegroundColor Yellow
}

# 2) Start tunnel
if ($Tool -eq 'cloudflare') {
  $cf = Get-Command cloudflared -ErrorAction SilentlyContinue
  if (-not $cf) {
    Write-Host "cloudflared not found. Install: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/" -ForegroundColor Red
    exit 2
  }
  Write-Host ""
  Write-Host "Starting Cloudflare quick tunnel..." -ForegroundColor Green
  Write-Host "Copy the https://....trycloudflare.com URL below and share it." -ForegroundColor Green
  Write-Host "Tip: after you get the URL, set APP_URL to that https URL in .env and restart serve for correct links." -ForegroundColor DarkGray
  Write-Host ""
  & cloudflared tunnel --url "http://127.0.0.1:$Port"
}
else {
  $ng = Get-Command ngrok -ErrorAction SilentlyContinue
  if (-not $ng) {
    Write-Host "ngrok not found. Install from https://ngrok.com/download then re-run with -Tool ngrok" -ForegroundColor Red
    exit 2
  }
  Write-Host ""
  Write-Host "Starting ngrok..." -ForegroundColor Green
  Write-Host "Open http://127.0.0.1:4040 for the public URL." -ForegroundColor Green
  Write-Host ""
  & ngrok http $Port
}
