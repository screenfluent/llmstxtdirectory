<?php
require_once __DIR__ . '/database.php';

try {
    $db = new SQLite3(__DIR__ . '/votes.db');
    
    // Add is_featured column if it doesn't exist
    $db->exec('ALTER TABLE implementations ADD COLUMN is_featured BOOLEAN DEFAULT 0');
    
    echo "Migration completed successfully\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
