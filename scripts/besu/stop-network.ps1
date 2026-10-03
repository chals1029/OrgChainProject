$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$networkRoot = Join-Path $repoRoot 'infra/besu'

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker is required to stop the Besu network.'
}

Push-Location $networkRoot
try {
    docker compose down
} finally {
    Pop-Location
}

