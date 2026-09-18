# OrgChain — one-shot test runner (backend + auth/OTP + pages + DOCX)
# Usage:
#   .\scripts\run-all-tests.ps1
#   .\scripts\run-all-tests.ps1 -Open
#   .\scripts\run-all-tests.ps1 -SkipUi
#   .\scripts\run-all-tests.ps1 -Base http://127.0.0.1:8000

param(
  [switch]$Open,
  [switch]$SkipUi,
  [switch]$SkipPages,
  [switch]$SkipAuth,
  [string]$Base = $env:ORGCHAIN_BASE_URL
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$extra = @()
if ($Open) { $extra += '--open' }
if ($SkipUi) { $extra += '--skip-ui' }
if ($SkipPages) { $extra += '--skip-pages' }
if ($SkipAuth) { $extra += '--skip-auth' }
if ($Base) { $extra += @('--base', $Base) }

Write-Host ""
Write-Host "OrgChain — Run All Tests" -ForegroundColor Cyan
Write-Host "(backend + auth/OTP + page smoke + DOCX report)"
Write-Host ""

node scripts/run-all-tests.mjs @extra
exit $LASTEXITCODE
