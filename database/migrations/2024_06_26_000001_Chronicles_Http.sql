CREATE TABLE IF NOT EXISTS `tb_chronicles_http` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) NOT NULL UNIQUE,
    `timestamp` BIGINT UNSIGNED NOT NULL,
    `method` VARCHAR(10) NOT NULL,
    `uri` VARCHAR(2048) NOT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL,
    `duration_ms` FLOAT NOT NULL,
    `ip_address` VARCHAR(45),
    `headers` JSON,
    `query_params` JSON,
    `request_body` TEXT,
    `response_body` MEDIUMTEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_http_timestamp ON `tb_chronicles_http` (`timestamp`);
CREATE INDEX IF NOT EXISTS idx_http_uri ON `tb_chronicles_http` (`uri`(255));
CREATE INDEX IF NOT EXISTS idx_http_status_code ON `tb_chronicles_http` (`status_code`);
