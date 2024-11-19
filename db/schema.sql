CREATE TABLE IF NOT EXISTS implementations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    logo_url TEXT,
    description TEXT,
    llms_txt_url TEXT NOT NULL UNIQUE,
    has_full INTEGER DEFAULT 0,
    is_featured INTEGER DEFAULT 0,
    is_requested INTEGER DEFAULT 0,
    is_draft INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
