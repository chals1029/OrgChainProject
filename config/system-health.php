<?php

return [
    'snapshot_path' => storage_path('app/private/system-health/snapshot.json'),
    'max_age_seconds' => 900,
    'timeout_seconds' => 4,
    'block_max_age_seconds' => 30,
    // These are loopback-only defaults for the existing private four-validator deployment.
    // They are never included in public observations.
    'rpc_urls' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'SYSTEM_HEALTH_RPC_URLS',
        'http://127.0.0.1:8545,http://127.0.0.1:8546,http://127.0.0.1:8547,http://127.0.0.1:8548'
    ))))),
];
