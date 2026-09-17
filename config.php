<?php
/**
 * =====================================================
 *  Atk VPN — Configuration
 * =====================================================
 *  IMPORTANT: Keep this file OUTSIDE the public web root
 *  if possible, or block direct access to it via .htaccess.
 */

// ---------------- Database ----------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'atk_vpn');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// ---------------- Admin Panel ----------------
// NEVER store a plain-text password. Generate a hash by running this
// once in your terminal (replace 'YourStrongPassword'):
//
//   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
//
// Then paste the output below.
define('ADMIN_PASSWORD_HASH', '$2y$10$REPLACE.THIS.WITH.YOUR.OWN.GENERATED.HASH..............');

// ---------------- API ----------------
// Change this to a long, random, secret value.
define('API_SECRET_KEY', 'MY_SECRET_KEY_2026');
