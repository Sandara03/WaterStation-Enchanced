<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost,https://localhost,capacitor://localhost,http://127.0.0.1:9000')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
