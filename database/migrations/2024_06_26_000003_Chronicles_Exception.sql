-- Migração para a tabela de eventos de Exceção

CREATE TABLE IF NOT EXISTS `tb_chronicles_exception` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) NOT NULL UNIQUE,
    `timestamp` BIGINT UNSIGNED NOT NULL,
    `class` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `file` VARCHAR(2048) NOT NULL,
    `line` INT UNSIGNED NOT NULL,
    `trace` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_exception_timestamp ON `tb_chronicles_exception` (`timestamp`);
CREATE INDEX IF NOT EXISTS idx_exception_class ON `tb_chronicles_exception` (`class`);
