-- Migração para a tabela de eventos SQL

CREATE TABLE IF NOT EXISTS `tb_chronicles_sql` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) NOT NULL UNIQUE,
    `timestamp` BIGINT UNSIGNED NOT NULL,
    `connection` VARCHAR(255) NOT NULL,
    `query` TEXT NOT NULL,
    `bindings` JSON,
    `duration_ms` FLOAT NOT NULL,
    `error` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_sql_timestamp ON `tb_chronicles_sql` (`timestamp`);
CREATE INDEX IF NOT EXISTS idx_sql_connection ON `tb_chronicles_sql` (`connection`);
