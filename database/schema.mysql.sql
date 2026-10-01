CREATE TABLE IF NOT EXISTS imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_filename VARCHAR(255) NOT NULL,
    file_sha256 CHAR(64) NOT NULL,
    status ENUM('processing', 'completed', 'failed') NOT NULL,
    rows_read INT UNSIGNED NOT NULL DEFAULT 0,
    rows_inserted INT UNSIGNED NOT NULL DEFAULT 0,
    duration_ms INT UNSIGNED NULL,
    error_message TEXT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    INDEX idx_imports_sha256 (file_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    source_row INT UNSIGNED NOT NULL,
    external_id VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    source VARCHAR(100) NOT NULL,
    utm_campaign VARCHAR(100) NULL,
    product VARCHAR(150) NOT NULL,
    budget_uah DECIMAL(12,2) NULL,
    status VARCHAR(32) NOT NULL,
    manager VARCHAR(100) NULL,
    comment TEXT NULL,
    next_contact_at DATETIME NULL,
    phone_is_valid TINYINT(1) NOT NULL,
    email_is_valid TINYINT(1) NOT NULL,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_leads_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    UNIQUE KEY uq_leads_import_row (import_id, source_row),
    INDEX idx_leads_external_id (external_id),
    INDEX idx_leads_import_id (import_id),
    INDEX idx_leads_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

