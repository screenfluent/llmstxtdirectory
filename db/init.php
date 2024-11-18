<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/environment.php';
require_once __DIR__ . '/database.php';

try {
    echo "Initializing database...\n";
    
    // Read schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql");
    }

    $db = new Database();
    
    // Execute schema
    echo "Applying database schema...\n";
    if ($db->executeRawSQL($schema) === false) {
        throw new Exception("Failed to execute schema");
    }

    // Check if we need to add sample data
    $result = $db->executeQuery('SELECT COUNT(*) as count FROM implementations');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];

    if ($count === 0) {
        echo "Adding sample data...\n";
        
        // Sample implementations
        $implementations = [
            [
                'name' => 'Superwall',
                'logo_url' => '/logos/superwall.svg',
                'description' => 'Paywall infrastructure for mobile apps',
                'llms_txt_url' => 'https://docs.superwall.com/llms.txt',
                'has_full' => 1,
                'is_featured' => 1,
                'is_requested' => 0,
                'is_draft' => 0,
                'votes' => 15
            ],
            [
                'name' => 'Anthropic',
                'logo_url' => '/logos/anthropic.svg',
                'description' => 'AI research company and creator of Claude',
                'llms_txt_url' => 'https://docs.anthropic.com/llms.txt',
                'has_full' => 1,
                'is_featured' => 1,
                'is_requested' => 0,
                'is_draft' => 0,
                'votes' => 12
            ]
        ];

        foreach ($implementations as $impl) {
            if ($db->addImplementation($impl)) {
                echo "Added implementation: {$impl['name']}\n";
            } else {
                echo "Failed to add implementation: {$impl['name']}\n";
            }
        }
    }

    echo "Database initialization completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
