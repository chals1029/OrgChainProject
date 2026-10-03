# OrgChain Hyperledger Besu test network

This directory replaces the old custom Node.js JSONL validator network with a
local Hyperledger Besu network. It is a development/thesis network only: the
generated keys are deterministic test keys and must not be reused in a real
institutional deployment.

The network has four QBFT validators. Laravel remains the application and
MySQL remains the source of business data. Laravel writes only SHA-256 record
proofs to the `OrgChainAnchor` contract, so voter choices and receipt images do
not go on-chain.

## Start it on Windows

Prerequisites:

1. Docker Desktop running with Linux containers.
2. PHP with the GMP extension enabled. Laragon users can run the commands below
   with `php -d extension=php_gmp.dll` if GMP is not enabled globally.

From the repository root:

```powershell
php -d extension=php_gmp.dll scripts/besu/generate-network.php
powershell -ExecutionPolicy Bypass -File scripts/besu/start-network.ps1
php -d extension=php_gmp.dll scripts/besu/deploy-contract.php
```

The first command generates `infra/besu/networkFiles/`, including the local
validator keys and the application signer key. The second command starts the
four QBFT nodes. The third deploys `OrgChainAnchor` and writes
`storage/app/besu/deployment.json`.

The checked-in contract artifacts are compiled for the `paris` EVM schedule so
they work with the local Besu genesis. If the Solidity source changes, rebuild
the artifacts with Docker before deploying:

```powershell
docker run --rm --mount "type=bind,source=$((Get-Location).Path)\infra\besu\contracts,target=/contracts" ethereum/solc:0.8.24 --bin --abi --optimize --evm-version paris -o /contracts/build /contracts/OrgChainAnchor.sol
Copy-Item infra/besu/contracts/build/OrgChainAnchor.bin infra/besu/contracts/OrgChainAnchor.bin -Force
Copy-Item infra/besu/contracts/build/OrgChainAnchor.abi infra/besu/contracts/OrgChainAnchor.abi -Force
```

Verify the network:

```powershell
Invoke-RestMethod http://127.0.0.1:8545 -Method Post -ContentType 'application/json' -Body '{"jsonrpc":"2.0","id":1,"method":"eth_chainId","params":[]}'
docker compose --project-directory infra/besu ps
```

Then set these values in the local `.env` and clear Laravel's cached config:

```dotenv
BLOCKCHAIN_DRIVER=besu
BESU_RPC_URL=http://127.0.0.1:8545
BESU_CHAIN_ID=20260920
BESU_VALIDATOR_COUNT=4
BESU_SIGNER_PRIVATE_KEY_FILE=infra/besu/networkFiles/app/key
BESU_DEPLOYMENT_FILE=storage/app/besu/deployment.json
# Optional; the deploy script defaults to 0x100000.
BESU_DEPLOY_GAS_LIMIT=0x100000
```

```powershell
php artisan config:clear
```

The old Node.js chain scripts are left in the repository for rollback and
historical test coverage, but they are not used when `BLOCKCHAIN_DRIVER=besu`.
To return to the current behavior, use `BLOCKCHAIN_DRIVER=file` and clear the
config cache.

Stop the test network with:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/besu/stop-network.ps1
```
