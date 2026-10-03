$ErrorActionPreference = 'Stop'

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$networkRoot = Join-Path $repoRoot 'infra/besu'
$generator = Join-Path $repoRoot 'scripts/besu/generate-network.php'
$networkFiles = Join-Path $networkRoot 'networkFiles'

if (-not (Test-Path (Join-Path $networkFiles 'genesis.json'))) {
    Write-Host 'Generating validator keys and QBFT genesis...'
    & php -d extension=php_gmp.dll $generator
    if ($LASTEXITCODE -ne 0) {
        throw 'Besu network generation failed. Make sure PHP GMP is enabled.'
    }
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker is required to run the Besu network. Install/start Docker Desktop, then run this script again.'
}

Push-Location $networkRoot
try {
    docker compose up -d
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Compose could not start the Besu network.'
    }
} finally {
    Pop-Location
}

Write-Host 'Besu QBFT network started. RPC: http://127.0.0.1:8545'
Write-Host 'Deploy the anchor contract with: php -d extension=php_gmp.dll scripts/besu/deploy-contract.php'

