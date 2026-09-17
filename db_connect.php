<?php
/**
 * =====================================================
 *  Atk VPN — Database Connection (PDO)
 * =====================================================
 */

require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO instance. Uses PDO prepared statements
 * everywhere (real prepares, not emulated) to prevent SQL injection.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Database connection failed. Please check the server error log.');
        }
    }

    return $pdo;
}
