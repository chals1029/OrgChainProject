# OrgChain slim blockchain node for helper laptops.
# Only needs: Node.js + ngrok. No full Laragon / MySQL required.
#
# Usage:
#   .\scripts\start-chain-node.ps1
#   .\scripts\start-chain-node.ps1 -NodeId 2 -Port 8001
#   .\scripts\start-chain-node.ps1 -NgrokToken "your-token" -NodeSecret "shared-secret"
#
# Then give the printed public URL to the MAIN OrgChain admin:
#   BLOCKCHAIN_NODE_2_URL=https://xxxx.ngrok-free.dev
#   BLOCKCHAIN_NODE_SECRET=<same secret>

param(
  [int]$NodeId = 2,
  [int]$Port = 8001,
  [string]$NgrokToken = $env:NGROK_AUTHTOKEN,
  [string]$NodeSecret = $env:ORGCHAIN_NODE_SECRET
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host " OrgChain Chain Node (slim validator)" -ForegroundColor Cyan
Write-Host " Laptop must stay ON while this runs" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# --- prerequisites ---
if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
  Write-Host "Node.js not found. Install from https://nodejs.org then re-run." -ForegroundColor Red
  exit 2
}
if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
  Write-Host "ngrok not found. Install from https://ngrok.com/download then re-run." -ForegroundColor Red
  exit 2
}

# --- prompts ---
if (-not $NgrokToken) {
  $NgrokToken = Read-Host "Paste your ngrok authtoken"
}
if (-not $NgrokToken) {
  Write-Host "ngrok token is required." -ForegroundColor Red
  exit 2
}

if (-not $NodeSecret) {
  $defaultSecret = "orgchain-node-auth-secret-2026"
  $entered = Read-Host "Node shared secret [Enter = use default from main .env]"
  $NodeSecret = if ($entered) { $entered } else { $defaultSecret }
}

$NodeIdInput = Read-Host "Node ID to run (2 or 3) [Enter = $NodeId]"
if ($NodeIdInput) { $NodeId = [int]$NodeIdInput }

Write-Host ""
Write-Host "Configuring ngrok..." -ForegroundColor Green
ngrok config add-authtoken $NgrokToken | Out-Null

# free any old listeners on port
$busy = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($busy) {
  Write-Host "Port $Port is busy. Pick another or stop the other process." -ForegroundColor Red
  exit 2
}

$env:ORGCHAIN_NODE_ID = "$NodeId"
$env:ORGCHAIN_NODE_PORT = "$Port"
$env:ORGCHAIN_NODE_SECRET = $NodeSecret
$env:BLOCKCHAIN_NODE_SECRET = $NodeSecret

Write-Host "Starting slim chain node on port $Port (node-$NodeId)..." -ForegroundColor Green
$nodeProc = Start-Process -FilePath "node" `
  -ArgumentList @("scripts/chain-node/server.mjs") `
  -WorkingDirectory $Root `
  -PassThru `
  -WindowStyle Minimized

Start-Sleep -Seconds 1
try {
  $health = Invoke-RestMethod -Uri "http://127.0.0.1:$Port/health" -TimeoutSec 5
  Write-Host "Node health OK (node_id=$($health.node_id))" -ForegroundColor Green
} catch {
  Write-Host "Node failed to start: $($_.Exception.Message)" -ForegroundColor Red
  if ($nodeProc -and -not $nodeProc.HasExited) { Stop-Process -Id $nodeProc.Id -Force -ErrorAction SilentlyContinue }
  exit 2
}

Write-Host "Starting ngrok tunnel..." -ForegroundColor Green
$ngrokProc = Start-Process -FilePath "ngrok" `
  -ArgumentList @("http", "$Port", "--log=stdout") `
  -WorkingDirectory $Root `
  -PassThru `
  -WindowStyle Minimized

# wait for public URL
$publicUrl = $null
for ($i = 0; $i -lt 30; $i++) {
  Start-Sleep -Seconds 1
  try {
    $api = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -TimeoutSec 2
    $https = $api.tunnels | Where-Object { $_.public_url -like "https://*" } | Select-Object -First 1
    if ($https) { $publicUrl = $https.public_url; break }
  } catch {}
}

if (-not $publicUrl) {
  Write-Host "Could not read ngrok public URL. Open http://127.0.0.1:4040" -ForegroundColor Yellow
} else {
  Write-Host ""
  Write-Host "============================================" -ForegroundColor Green
  Write-Host " NODE ONLINE — send this to MAIN admin" -ForegroundColor Green
  Write-Host "============================================" -ForegroundColor Green
  Write-Host ""
  Write-Host " Public URL : $publicUrl"
  Write-Host " Node ID    : $NodeId"
  Write-Host " Secret     : (same as main BLOCKCHAIN_NODE_SECRET)"
  Write-Host ""
  Write-Host " On MAIN OrgChain .env set:"
  Write-Host "   BLOCKCHAIN_NODE_${NodeId}_URL=$publicUrl"
  Write-Host "   BLOCKCHAIN_NODE_SECRET=$NodeSecret"
  Write-Host ""
  Write-Host " Keep this window/process running. Close = node offline."
  Write-Host "============================================" -ForegroundColor Green
  Write-Host ""

  # also write a helper file for copy/paste
  $out = Join-Path $Root "storage\app\chain-node\node-$NodeId-connection.txt"
  New-Item -ItemType Directory -Force -Path (Split-Path $out) | Out-Null
  @"
OrgChain Chain Node connection
Generated: $(Get-Date -Format o)
NODE_ID=$NodeId
PUBLIC_URL=$publicUrl
BLOCKCHAIN_NODE_${NodeId}_URL=$publicUrl
BLOCKCHAIN_NODE_SECRET=$NodeSecret
"@ | Set-Content -Path $out -Encoding UTF8
  Write-Host "Saved: $out"
}

Write-Host ""
Write-Host "Press Ctrl+C to stop node + ngrok." -ForegroundColor Yellow

try {
  while ($true) {
    if ($nodeProc.HasExited) { Write-Host "Node process exited."; break }
    if ($ngrokProc.HasExited) { Write-Host "ngrok process exited."; break }
    Start-Sleep -Seconds 3
  }
} finally {
  if ($ngrokProc -and -not $ngrokProc.HasExited) { Stop-Process -Id $ngrokProc.Id -Force -ErrorAction SilentlyContinue }
  if ($nodeProc -and -not $nodeProc.HasExited) { Stop-Process -Id $nodeProc.Id -Force -ErrorAction SilentlyContinue }
  Write-Host "Stopped."
}
