<?php
// Simple health check for Railway debugging

header('Content-Type: application/json');

$checks = [
    'php_version' => PHP_VERSION,
    'app_key_set' => !empty(getenv('APP_KEY')),
    'db_host' => getenv('DB_HOST') ?: 'not set',
    'db_connection' => getenv('DB_CONNECTION') ?: 'not set',
    'app_env' => getenv('APP_ENV') ?: 'not set',
    'storage_writable' => is_writable(__DIR__ . '/../storage'),
    'bootstrap_cache_writable' => is_writable(__DIR__ . '/../bootstrap/cache'),
];

// Try database connection
try {
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: 3306;
    $db = getenv('DB_DATABASE');
    $user = getenv('DB_USERNAME');
    $pass = getenv('DB_PASSWORD');
    
    if ($host && $db && $user) {
        $dsn = "mysql:host=$host;port=$port;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass);
        $checks['database_connection'] = 'success';
    } else {
        $checks['database_connection'] = 'missing credentials';
    }
} catch (Exception $e) {
    $checks['database_connection'] = 'failed: ' . $e->getMessage();
}

echo json_encode($checks, JSON_PRETTY_PRINT);
