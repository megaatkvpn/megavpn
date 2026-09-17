<?php
/**
 * =====================================================
 *  Atk VPN — Public API (api.php)
 * =====================================================
 *  Returns the list of Active servers as JSON.
 *
 *  Auth: either
 *    - Header:  X-API-KEY: MY_SECRET_KEY_2026
 *    - or GET:  ?api_key=MY_SECRET_KEY_2026
 *
 *  Obfuscation: the Android app does
 *      String realBase64 = obfuscatedBase64.substring(1);
 *  i.e. it drops the character at index 0 (the first character).
 *  So this API must PREPEND one random alphanumeric character
 *  before sending the string out, so that after the app strips
 *  it off, the original Base64 string is recovered exactly.
 */

require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Prepends one random alphanumeric character to the string.
 * Reversed on the Android client via obfuscatedBase64.substring(1).
 */
function obfuscate_base64(string $base64): string
{
    if ($base64 === '') {
        return $base64;
    }
    $chars    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randChar = $chars[random_int(0, strlen($chars) - 1)];

    // Random char goes first, then the untouched original string
    return $randChar . $base64;
}

/* ---------------- API key check ---------------- */
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');

if ($providedKey === '' || !hash_equals(API_SECRET_KEY, (string)$providedKey)) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Forbidden: invalid or missing API key',
    ]);
    exit;
}

/* ---------------- Fetch + respond ---------------- */
try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        "SELECT id, server_name, country_code, ovpn_base64
         FROM vpn_servers
         WHERE status = 'Active'
         ORDER BY server_name ASC"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $servers = [];
    foreach ($rows as $row) {
        $servers[] = [
            'id'           => (int)$row['id'],
            'server_name'  => $row['server_name'],
            'country_code' => $row['country_code'],
            'ovpn_base64'  => obfuscate_base64($row['ovpn_base64']),
        ];
    }

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'count'   => count($servers),
        'servers' => $servers,
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Internal server error',
    ]);
}
