CREATE TABLE IF NOT EXISTS imports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_filename TEXT NOT NULL,
    file_sha256 TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('processing', 'completed', 'failed')),
    rows_read INTEGER NOT NULL DEFAULT 0,
    rows_inserted INTEGER NOT NULL DEFAULT 0,
    duration_ms INTEGER,
    error_message TEXT,
    started_at TEXT NOT NULL,
    finished_at TEXT
);

CREATE TABLE IF NOT EXISTS leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_id INTEGER NOT NULL,
    source_row INTEGER NOT NULL,
    external_id TEXT NOT NULL,
    created_at TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT,
    city TEXT NOT NULL,
    source TEXT NOT NULL,
    utm_campaign TEXT,
    product TEXT NOT NULL,
    budget_uah NUMERIC,
    status TEXT NOT NULL,
    manager TEXT,
    comment TEXT,
    next_contact_at TEXT,
    phone_is_valid INTEGER NOT NULL,
    email_is_valid INTEGER NOT NULL,
    imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    UNIQUE (import_id, source_row)
);

CREATE INDEX IF NOT EXISTS idx_leads_external_id ON leads(external_id);
CREATE INDEX IF NOT EXISTS idx_leads_import_id ON leads(import_id);
CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status);

