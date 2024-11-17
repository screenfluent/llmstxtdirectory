<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database.php';

try {
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql");
    }

    $db = new Database();
    
    // Always apply schema (handles both new and existing databases)
    $result = $db->db->exec($schema);
    if ($result === false) {
        throw new Exception("Failed to execute schema: " . $db->db->lastErrorMsg());
    }

    // Only add sample data if no implementations exist
    $stmt = $db->db->prepare('SELECT COUNT(*) as count FROM implementations');
    $result = $stmt->execute();
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
                'votes' => 25
            ]
        ];

        // Requested implementations
        $requested = [
            [
                'name' => 'Vercel',
                'logo_url' => '/logos/vercel.png',
                'description' => 'Frontend cloud platform and framework provider',
                'llms_txt_url' => 'https://vercel.com/docs/llms.txt',
                'has_full' => 0,
                'is_featured' => 0,
                'is_requested' => 1,
                'is_draft' => 0,
                'votes' => 42
            ],
            [
                'name' => 'Next.js',
                'logo_url' => '/logos/nextjs.png',
                'description' => 'React framework for production',
                'llms_txt_url' => 'https://nextjs.org/docs/llms.txt',
                'has_full' => 0,
                'is_featured' => 0,
                'is_requested' => 1,
                'is_draft' => 0,
                'votes' => 38
            ]
        ];

        // Insert all implementations
        foreach (array_merge($implementations, $requested) as $impl) {
            if (!$db->addImplementation($impl)) {
                error_log("Failed to add implementation: {$impl['name']}");
            }
        }
    } else {
        echo "Database already contains data, skipping sample data.\n";
    }

    echo "Database initialization/update completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
