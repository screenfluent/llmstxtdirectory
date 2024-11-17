<?php
require_once __DIR__ . '/database.php';

try {
    $db = new SQLite3(__DIR__ . '/votes.db');
    
    // Update existing featured implementations
    $featured_urls = [
        'https://superwall.com/docs/llms.txt',
        'http://docs.anthropic.com/llms.txt',
        'https://docs.cursor.com/llms.txt',
        'https://docs.fastht.ml/llms.txt',
        'https://nbdev.fast.ai/llms.txt',
        'https://fastcore.fast.ai/llms.txt',
        'https://answer.ai/llms.txt'
    ];
    
    foreach ($featured_urls as $url) {
        $db->exec('UPDATE implementations SET is_featured = 1 WHERE llms_txt_url = "' . SQLite3::escapeString($url) . '"');
    }
    
    echo "Featured implementations updated successfully\n";
} catch (Exception $e) {
    echo "Update error: " . $e->getMessage() . "\n";
}
