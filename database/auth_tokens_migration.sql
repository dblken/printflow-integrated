-- Persistent login tokens (Remember Me)
-- PrintFlow - Run once to create auth_tokens table
-- Run this in phpMyAdmin (SQL tab) or: mysql -u root -p printflow < database/auth_tokens_migration.sql

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('Admin','Staff','Customer') NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_token (token_hash),
    KEY idx_user (user_id, user_type),
    KEY idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
