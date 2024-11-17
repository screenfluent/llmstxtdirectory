CREATE TABLE IF NOT EXISTS implementations (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    logo_url TEXT NOT NULL,
    description TEXT,
    llms_txt_url TEXT NOT NULL,
    has_full BOOLEAN DEFAULT 0,
    is_requested BOOLEAN DEFAULT 0,
    is_featured BOOLEAN DEFAULT 0,
    votes INTEGER DEFAULT 0,
    is_draft INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS votes (
    id INTEGER PRIMARY KEY,
    implementation_id INTEGER,
    user_ip TEXT,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(implementation_id) REFERENCES implementations(id)
);
