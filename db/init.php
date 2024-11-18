<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/environment.php';
require_once __DIR__ . '/database.php';

try {
    echo "Initializing database...\n";
    
    $db = new Database();

    // In staging, always recreate the database
    if (!isProduction()) {
        echo "Recreating database in staging environment...\n";
        $db->recreateDatabase();
    }
    
    // Read schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql");
    }
    
    // Execute schema
    echo "Applying database schema...\n";
    if ($db->executeRawSQL($schema) === false) {
        throw new Exception("Failed to execute schema");
    }

    // In staging, always add sample data
    if (!isProduction()) {
        echo "Adding sample data for staging environment...\n";
        
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
            ],
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

        foreach ($implementations as $impl) {
            if ($db->addImplementation($impl)) {
                echo "Added implementation: {$impl['name']}\n";
            } else {
                echo "Failed to add implementation: {$impl['name']}\n";
            }
        }
    } else {
        // In production, only add sample data if database is empty
        $result = $db->executeQuery('SELECT COUNT(*) as count FROM implementations');
        $count = $result->fetchArray(SQLITE3_ASSOC)['count'];

        if ($count === 0) {
            echo "Adding sample data to empty production database...\n";
            // Add only verified implementations in production
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
    }

    echo "Database initialization completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
