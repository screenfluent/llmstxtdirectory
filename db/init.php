<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/environment.php';
require_once __DIR__ . '/database.php';

try {
    logPerformanceMetric('database_init', 'start');
    
    $db = new Database();

    // In staging, always recreate the database
    if (!isProduction()) {
        logPerformanceMetric('database_init', 'recreate_start');
        $db->recreateDatabase();
        logPerformanceMetric('database_init', 'recreate_end');
    }
    
    // Read schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read schema.sql");
    }
    
    // Execute schema
    logPerformanceMetric('database_init', 'schema_start');
    if ($db->executeRawSQL($schema) === false) {
        throw new Exception("Failed to execute schema");
    }
    logPerformanceMetric('database_init', 'schema_end');

    // In staging, always add sample data
    if (!isProduction()) {
        logPerformanceMetric('database_init', 'sample_data_start');
        
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
                logPerformanceMetric('database_init', 'add_implementation', ['name' => $impl['name']]);
            } else {
                logError('Failed to add implementation', ['name' => $impl['name']]);
            }
        }
        logPerformanceMetric('database_init', 'sample_data_end');
    } else {
        // In production, only add sample data if database is empty
        $result = $db->executeQuery('SELECT COUNT(*) as count FROM implementations');
        $count = $result->fetchArray(SQLITE3_ASSOC)['count'];

        if ($count === 0) {
            logPerformanceMetric('database_init', 'production_sample_data_start');
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
                    logPerformanceMetric('database_init', 'add_implementation', ['name' => $impl['name']]);
                } else {
                    logError('Failed to add implementation', ['name' => $impl['name']]);
                }
            }
            logPerformanceMetric('database_init', 'production_sample_data_end');
        }
    }

    logPerformanceMetric('database_init', 'complete');
} catch (Exception $e) {
    logError('Database initialization failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}
