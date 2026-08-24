<?php

return [
    'environment' => env('APP_ENV', 'production'),
    'app_name' => env('VOTING_APP_NAME', 'OrgChain Official Voting'),
    'app_url' => env('VOTING_APP_URL', env('APP_URL', '')),
    'url_prefix' => env('VOTING_URL_PREFIX', '/voting-system'),
    'trusted_hosts' => env('TRUSTED_HOSTS', 'localhost,127.0.0.1'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),

    'session' => [
        'path' => storage_path('app/voting/sessions'),
    ],

    // Used only as fallback if Laravel DB connection is unavailable.
    'database' => [
        'driver' => env('VOTING_DB_DRIVER', env('DB_CONNECTION', 'mysql') === 'sqlite' ? 'mysql' : env('DB_CONNECTION', 'mysql')),
        'host' => env('VOTING_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('VOTING_DB_PORT', env('DB_PORT', '3306')),
        'name' => env('VOTING_DB_DATABASE', env('DB_DATABASE', 'votingsystem')),
        'username' => env('VOTING_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('VOTING_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => 'utf8mb4',
        'seed_demo' => filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', ''),
        'allowed_domain' => env('GOOGLE_ALLOWED_DOMAIN', 'g.batstate-u.edu.ph'),
    ],

    'mail' => [
        'mailer' => env('MAIL_MAILER', 'smtp'),
        'host' => env('MAIL_HOST', ''),
        'port' => env('MAIL_PORT', '587'),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', 'no-reply@localhost')),
        'from_name' => env('MAIL_FROM_NAME', env('VOTING_APP_NAME', 'OrgChain Official Voting')),
        'log_path' => storage_path('logs/voting-mail.log'),
    ],

    'security' => [
        'admin_login_path' => env('ADMIN_LOGIN_PATH', '/ssc-access-c7b4f2e91a6d'),
        'staff_login_url' => env('STAFF_LOGIN_URL', ''),
        'canvassing_dashboard_path' => env('CANVASSING_DASHBOARD_PATH', '/ssc-canvassing-dashboard-d8f3b72a4e91'),
        'canvassing_tally_path' => env('CANVASSING_TALLY_PATH', '/ssc-canvassing-tally-73a6e2d4b8c9'),
        'canvassing_reports_path' => env('CANVASSING_REPORTS_PATH', '/ssc-canvassing-reports-b61e7a42c9f8'),
        'canvassing_reports_pin' => env('CANVASSING_REPORTS_PIN', ''),
        'global_rate_limit' => (int) env('SECURITY_GLOBAL_RATE_LIMIT', 1200),
        'global_rate_window' => (int) env('SECURITY_GLOBAL_RATE_WINDOW', 60),
        'public_rate_limit' => (int) env('SECURITY_PUBLIC_RATE_LIMIT', 600),
        'public_rate_window' => (int) env('SECURITY_PUBLIC_RATE_WINDOW', 60),
        'staff_rate_limit' => (int) env('SECURITY_STAFF_RATE_LIMIT', 120),
        'staff_rate_window' => (int) env('SECURITY_STAFF_RATE_WINDOW', 300),
        'session_idle_seconds' => (int) env('SECURITY_SESSION_IDLE_SECONDS', 1800),
        'session_absolute_seconds' => (int) env('SECURITY_SESSION_ABSOLUTE_SECONDS', 28800),
    ],

    'nodes' => [
        'current_node' => (int) env('BLOCKCHAIN_CURRENT_NODE', 1),
        'secret_token' => env('BLOCKCHAIN_NODE_SECRET', 'orgchain-node-auth-secret-2026'),
        'timeout_seconds' => (int) env('BLOCKCHAIN_NODE_TIMEOUT', 3),
        'urls' => [
            1 => env('BLOCKCHAIN_NODE_1_URL', 'local'),
            2 => env('BLOCKCHAIN_NODE_2_URL', 'local'),
            3 => env('BLOCKCHAIN_NODE_3_URL', 'local'),
        ],
    ],
];

