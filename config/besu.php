<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OrgChain blockchain driver
    |--------------------------------------------------------------------------
    |
    | The file driver remains the safe default for an existing installation.
    | Set BLOCKCHAIN_DRIVER=besu after the local QBFT network and anchor
    | contract have been started.
    |
    */
    'driver' => strtolower((string) env('BLOCKCHAIN_DRIVER', 'file')),
    'enabled' => strtolower((string) env('BLOCKCHAIN_DRIVER', 'file')) === 'besu',

    'rpc_url' => env('BESU_RPC_URL', 'http://127.0.0.1:8545'),
    'chain_id' => (int) env('BESU_CHAIN_ID', 20260920),
    'contract_address' => trim((string) env('BESU_CONTRACT_ADDRESS', '')),
    'deployment_file' => env('BESU_DEPLOYMENT_FILE', storage_path('app/besu/deployment.json')),

    'signer_address' => trim((string) env('BESU_SIGNER_ADDRESS', '')),
    'signer_private_key' => trim((string) env('BESU_SIGNER_PRIVATE_KEY', '')),
    'signer_private_key_file' => env(
        'BESU_SIGNER_PRIVATE_KEY_FILE',
        base_path('infra/besu/networkFiles/app/key')
    ),

    'validator_count' => max(1, (int) env('BESU_VALIDATOR_COUNT', 4)),
    'rpc_timeout_seconds' => max(1, (int) env('BESU_RPC_TIMEOUT_SECONDS', 5)),
    'receipt_timeout_seconds' => max(5, (int) env('BESU_RECEIPT_TIMEOUT_SECONDS', 45)),
    'poll_interval_ms' => max(50, (int) env('BESU_POLL_INTERVAL_MS', 250)),
    'gas_limit' => trim((string) env('BESU_ANCHOR_GAS_LIMIT', '0x493e0')),
    'deploy_gas_limit' => trim((string) env('BESU_DEPLOY_GAS_LIMIT', '0x100000')),
    'gas_price_wei' => trim((string) env('BESU_GAS_PRICE_WEI', '1')),
];
