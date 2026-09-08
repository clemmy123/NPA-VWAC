<?php

return [
    'enabled' => (bool) env('JUMUISHI_ENABLED', true),
    'url' => env('JUMUISHI_URL', 'http://127.0.0.1:8000'),
    'module_path' => env('JUMUISHI_MODULE_PATH', 'npa-vwac'),
    'api_secret' => env('JUMUISHI_API_SECRET'),
    'platform_secret' => env('JUMUISHI_PLATFORM_SECRET'),
    'default_role' => env('JUMUISHI_DEFAULT_LOCAL_ROLE', 'Data Entry User'),
    'sso_exchange_path' => env('JUMUISHI_SSO_EXCHANGE_PATH', '/api/internal/sso/exchange'),
    'sso_start_path' => env('JUMUISHI_SSO_START_PATH', '/sso/start'),
    'central_logout_path' => env('JUMUISHI_CENTRAL_LOGOUT_PATH', '/central-logout'),
    'password_path' => env('JUMUISHI_PASSWORD_PATH', '/profile'),
    'user_sync_path' => env('JUMUISHI_USER_SYNC_PATH', '/api/internal/users/sync'),
    'connect_timeout' => (int) env('JUMUISHI_CONNECT_TIMEOUT', 5),
    'request_timeout' => (int) env('JUMUISHI_REQUEST_TIMEOUT', 10),
];
