<?php
// Database settings come from environment variables (hosting) and fall back to local XAMPP defaults.
//   DB_HOST DB_PORT DB_USER DB_PASS DB_NAME  - connection;  DB_SSL=1 - use TLS (required by TiDB Cloud)
$servername = getenv('DB_HOST') ?: 'localhost';
$username   = getenv('DB_USER') ?: 'root';
$password   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname     = getenv('DB_NAME') ?: 'Covid';
$port       = (int)(getenv('DB_PORT') ?: 3306);
try {
    $conn = mysqli_init();
    $flags = 0;
    if (getenv('DB_SSL')) {
        $conn->ssl_set(null, null, getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt', null, null);
        $flags = MYSQLI_CLIENT_SSL;
    }
    if (!$conn->real_connect($servername, $username, $password, $dbname, $port, null, $flags)) {
        throw new Exception($conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());   // details go to the server log, not to visitors
    http_response_code(500);
    die('Database connection failed.');
}
