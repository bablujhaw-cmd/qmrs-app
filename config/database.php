<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): mixed {
    static $connection = null;
    if ($connection !== null) {
        return $connection;
    }
    if (!function_exists('oci_connect')) {
        throw new RuntimeException('OCI8 PHP extension is not installed.');
    }
    $connection = @oci_connect(DB_USERNAME, DB_PASSWORD, DB_CONNECTION_STRING, 'AL32UTF8');
    if ($connection === false) {
        $e = oci_error();
        error_log('Oracle connection error: ' . ($e['message'] ?? 'Unknown'));
        throw new RuntimeException('Database connection failed.');
    }
    return $connection;
}