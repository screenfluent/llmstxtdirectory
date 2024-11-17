<?php
require_once __DIR__ . '/database.php';

$db = new Database();
$pdo = new SQLite3(__DIR__ . '/votes.db');

// Check if we already have data
$result = $pdo->query('SELECT COUNT(*) as count FROM implementations');
$count = $result->fetchArray(SQLITE3_ASSOC)['count'];

if ($count > 0) {
    echo "Database already contains data. Skipping initialization.\n";
    exit;
}

// Regular implementations
$implementations = [
    [
        'name' => 'Superwall',
        'logo_url' => 'https://superwall.com/logo.svg',
        'llms_txt_url' => 'https://superwall.com/docs/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ],
    [
        'name' => 'Anthropic',
        'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Anthropic_logo.svg',
        'llms_txt_url' => 'http://docs.anthropic.com/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ],
    [
        'name' => 'Cursor',
        'logo_url' => 'https://cursor.sh/cursor.svg',
        'llms_txt_url' => 'https://docs.cursor.com/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ],
    [
        'name' => 'FastHTML',
        'logo_url' => 'https://fastht.ml/logo.png',
        'llms_txt_url' => 'https://docs.fastht.ml/llms.txt',
        'has_full' => false,
        'is_requested' => false
    ],
    [
        'name' => 'nbdev',
        'logo_url' => 'https://nbdev.fast.ai/images/logo.png',
        'llms_txt_url' => 'https://nbdev.fast.ai/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ],
    [
        'name' => 'fastcore',
        'logo_url' => 'https://fastcore.fast.ai/images/logo.png',
        'llms_txt_url' => 'https://fastcore.fast.ai/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ],
    [
        'name' => 'Answer.AI',
        'logo_url' => 'https://answer.ai/logo.png',
        'llms_txt_url' => 'https://answer.ai/llms.txt',
        'has_full' => true,
        'is_requested' => false
    ]
];

// Requested implementations
$requested = [
    [
        'name' => 'Vercel',
        'logo_url' => 'https://assets.vercel.com/image/upload/front/favicon/vercel/180x180.png',
        'description' => 'Frontend cloud platform and framework provider',
        'llms_txt_url' => '',
        'has_full' => false,
        'is_requested' => true,
        'votes' => 42
    ],
    [
        'name' => 'Next.js',
        'logo_url' => 'https://nextjs.org/static/favicon/favicon-32x32.png',
        'description' => 'React framework for production-grade applications',
        'llms_txt_url' => '',
        'has_full' => false,
        'is_requested' => true,
        'votes' => 38
    ],
    [
        'name' => 'Stripe',
        'logo_url' => 'https://stripe.com/img/v3/home/twitter.png',
        'description' => 'Payment processing platform for internet businesses',
        'llms_txt_url' => '',
        'has_full' => false,
        'is_requested' => true,
        'votes' => 35
    ]
];

// Insert all implementations
foreach (array_merge($implementations, $requested) as $impl) {
    $stmt = $pdo->prepare('INSERT INTO implementations (name, logo_url, description, llms_txt_url, has_full, is_requested, votes) VALUES (:name, :logo_url, :description, :llms_txt_url, :has_full, :is_requested, :votes)');
    $stmt->bindValue(':name', $impl['name'], SQLITE3_TEXT);
    $stmt->bindValue(':logo_url', $impl['logo_url'], SQLITE3_TEXT);
    $stmt->bindValue(':description', $impl['description'] ?? '', SQLITE3_TEXT);
    $stmt->bindValue(':llms_txt_url', $impl['llms_txt_url'], SQLITE3_TEXT);
    $stmt->bindValue(':has_full', $impl['has_full'] ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':is_requested', $impl['is_requested'] ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':votes', $impl['votes'] ?? 0, SQLITE3_INTEGER);
    $stmt->execute();
}

echo "Database initialized with sample data.\n";
