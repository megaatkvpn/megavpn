-- =====================================================
-- Atk VPN — Database Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS atk_vpn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atk_vpn;

CREATE TABLE IF NOT EXISTS vpn_servers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_name   VARCHAR(100)        NOT NULL,
    country_code  VARCHAR(10)         NOT NULL,
    ovpn_base64   LONGTEXT            NOT NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example row (optional — remove or edit as needed)
-- INSERT INTO vpn_servers (server_name, country_code, ovpn_base64, status)
-- VALUES ('Japan Server 1', 'JP', TO_BASE64('client\ndev tun\nproto udp\n...'), 'Active');
