<?php

return [
    'name' => 'Calandria RSS Aggregator',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => (bool) ($_ENV['APP_DEBUG'] ?? false),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'en',
];
