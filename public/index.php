<?php 
// Prevent caching for staging
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'staging.') === 0) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

require_once __DIR__ . '/../includes/environment.php';
require_once __DIR__ . '/../includes/monitoring.php';
$requestStart = startRequestTiming();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>llmstxt.directory - Index of llms.txt Implementations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (isProduction() || isStaging()): ?>
    <script
        src="https://beamanalytics.b-cdn.net/beam.min.js"
        data-token="93f53d9b-fadc-433a-9c7c-9621ac1ee672"
        async
    ></script>
    <?php endif; ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Space Grotesk', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .nav {
            background: #FAFAFA;
            border-bottom: 1px solid #E3E3E3;
            padding: 12px 0;
        }
        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .nav-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }
        .submit-button {
            padding: 6px 12px;
            background: #333;
            color: white;
            border: none;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.9em;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .submit-button:hover {
            background: #444;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: #FAFAFA;
            border-radius: 10px;
            border: 1px solid #E3E3E3;
        }
        .header p {
            color: #666;
            max-width: 800px;
            margin: 20px auto;
            line-height: 1.6;
        }
        .header-links {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        .header-link {
            color: #1976d2;
            text-decoration: none;
        }
        .header-link:hover {
            text-decoration: underline;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 0;
        }
        .card {
            background: #FAFAFA;
            border-radius: 10px;
            border: 1px solid #E3E3E3;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            position: relative;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .card:hover .external-link {
            opacity: 1;
        }
        .external-link {
            position: absolute;
            top: 12px;
            right: 12px;
            opacity: 0;
            transition: opacity 0.2s;
            width: 16px;
            height: 16px;
            color: #666;
            z-index: 1;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            cursor: pointer;
        }
        .card-inner {
            padding: 15px;
            position: relative;
        }
        .card .logo-wrapper {
            width: 32px;
            height: 32px;
            flex: none;
            border-radius: 6px;
            background: white;
            padding: 3px;
            border: 1px solid #E3E3E3;
        }
        .logo-wrapper img {
            width: 100%;
            height: 100%;
            border-radius: 3px;
        }
        .card-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 95px;
        }
        .description-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .card-description {
            color: #666;
            font-size: 0.9em;
            margin: 0;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
            min-height: 2.8em;
        }
        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title {
            font-size: 1.2em;
            font-weight: bold;
            margin: 2px 0 0 0;
            padding: 0;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-labels {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 64px;
        }
        .label {
            font-size: 0.8em;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            white-space: nowrap;
            border: 1px solid rgba(73, 80, 87, 0.08);
            transition: background-color 0.2s;
        }
        .label:hover {
            background: #e2e6e9;
        }
        .label-txt {
            background: #f3e5f5;
            color: #6a1b9a;
            border: 1px solid rgba(106, 27, 154, 0.08);
        }
        .label-txt:hover {
            background: #e1bee7;
        }
        .label-full {
            background: #e3f2fd;
            color: #0d47a1;
            border: 1px solid rgba(13, 71, 161, 0.08);
        }
        .label-full:hover {
            background: #bbdefb;
        }
        .section-title {
            font-size: 1.5em;
            color: #333;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E3E3E3;
        }
        .requested-card {
            background: #FAFAFA;
            border-radius: 10px;
            border: 1px solid #E3E3E3;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .requested-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .requested-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }
        .requested-info {
            flex: 1;
        }
        .requested-name {
            font-size: 1.2em;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: #333;
        }
        .requested-description {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
        .upvote-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #E3E3E3;
            border-radius: 6px;
            background: white;
            color: #666;
            font-family: inherit;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.2s;
        }
        .upvote-button:hover {
            background: #f5f5f5;
        }
        .upvote-count {
            font-weight: 500;
        }
        .site-footer {
            background: #FAFAFA;
            border-top: 1px solid #E3E3E3;
            padding: 40px 0;
            margin-top: 40px;
            color: #666;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-links {
            display: flex;
            gap: 20px;
        }
        .footer-links a {
            color: #666;
            text-decoration: none;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
        }
        .requested-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }
        .hero {
            padding: 40px 0;
            background: #FAFAFA;
            border-bottom: 1px solid #E3E3E3;
        }
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .hero h1 {
            font-size: 2.4em;
            margin: 0 0 40px 0;
            color: #333;
            font-weight: 600;
            line-height: 1.2;
        }
        .hero h1 span {
            display: block;
            font-size: 0.45em;
            color: #666;
            font-weight: 400;
            margin-top: 10px;
            font-style: italic;
        }
        .hero-features {
            margin: 0 0 40px 0;
            color: #555;
            line-height: 1.8;
        }
        .hero-features h2 {
            font-size: 1em;
            color: #333;
            font-weight: 500;
            margin: 0 0 10px 0;
        }
        .hero-features ul {
            margin: 0;
            padding-left: 20px;
        }
        .what-is {
            margin: 0;
        }
        .what-is h2, .why-section h2 {
            font-size: 1.3em;
            color: #333;
            margin: 0 0 10px 0;
        }
        .what-is p {
            color: #555;
            margin: 0 0 15px 0;
            line-height: 1.6;
        }
        .what-is p:last-child {
            margin-bottom: 0;
        }
        .what-is .learn-more {
            color: #2196f3;
            text-decoration: none;
        }
        .what-is .learn-more:hover {
            text-decoration: underline;
        }
        .why-section {
            margin: 60px 0 0 0;
        }
        .why-section h3 {
            font-size: 1.1em;
            color: #333;
            margin: 0 0 10px 0;
        }
        .why-section p {
            color: #555;
            margin: 0 0 20px 0;
            line-height: 1.6;
        }
        .why-section ul {
            margin: 0 0 20px 0;
            padding-left: 20px;
            list-style-type: none;
        }
        .why-section li {
            color: #555;
            margin: 0 0 12px 0;
            line-height: 1.6;
            position: relative;
            padding-left: 5px;
        }
        .why-section li::before {
            content: "•";
            position: absolute;
            left: -15px;
            color: #333;
        }
        .why-section li:last-child {
            margin-bottom: 0;
        }
        code.llms {
            color: #6a1b9a;
        }
        .directory-section {
            margin-bottom: 40px;
        }
        .directory-section:last-child {
            margin-bottom: 0;
        }
        .directory-section h2 {
            font-size: 1.3em;
            color: #333;
            margin: 0 0 10px 0;
        }
        .section-divider {
            border: none;
            border-top: 1px solid #E3E3E3;
            margin: 40px 0;
        }
    </style>
    <script>
        async function handleUpvote(implementationId) {
            const button = event.currentTarget;
            const countSpan = button.querySelector('.upvote-count');
            const currentCount = parseInt(countSpan.textContent);
            
            // Disable button temporarily
            button.disabled = true;
            
            try {
                const response = await fetch('/api/vote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ implementationId })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                // Update count on success
                countSpan.textContent = currentCount + 1;
                
                // Add visual feedback
                button.style.background = '#f0f0f0';
                setTimeout(() => {
                    button.style.background = '';
                }, 500);
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to register vote');
            } finally {
                button.disabled = false;
            }
        }
    </script>
</head>
<body>
    <nav class="nav">
        <div class="nav-content">
            <a href="/" class="nav-title">llmstxt.directory</a>
            <a href="https://github.com/jph00/llms-txt" class="submit-button">Submit</a>
        </div>
    </nav>
    <div class="hero">
        <div class="hero-content">
            <h1>
                /llms.txt Directory
                <span>Index of AI-friendly technical documentation using the llms.txt standard</span>
            </h1>
            
            <!--
            <div class="hero-features">
                <h2>Find projects with structured documentation for:</h2>
                <ul>
                    <li>IDE integrations</li>
                    <li>Up-to-date version references</li>
                    <li>Implementation guides</li>
                </ul>
            </div>
            -->

            <div class="what-is">
                <h2>What is the <code><strong>/llms.txt</strong></code> file?</h2>
                <p>A web standard that helps AI tools better understand technical documentation through a structured markdown file at <code><strong>/llms.txt</strong></code>. By providing concise, machine-readable information, it enables AI assistants to give more accurate and contextual responses about your project or tool.</p>
                <p>Similar to how <code><strong>robots.txt</strong></code> guides search engines, <code><strong>/llms.txt</strong></code> helps AI models like ChatGPT, Claude, Llama, etc understand your documentation's structure and content. This standardized format ensures that developers get precise, up-to-date information when using AI-powered development tools.</p>
                <p>For more information about the standard, visit the <a href="https://llmstxt.org/" class="learn-more" target="_blank">official website</a>.</p>
            </div>

        </div>
    </div>
    <div class="container">
        <div class="directory-sections">
            <?php
            require_once __DIR__ . '/../db/database.php';
            require_once __DIR__ . '/../includes/helpers.php';

            $db = new Database();

            // Initialize featured implementations if they don't exist
            $featuredImplementations = [
                [
                    'name' => 'Superwall',
                    'logo_url' => 'https://superwall.com/logo.svg',
                    'llms_txt_url' => 'https://superwall.com/docs/llms.txt',
                    'has_full' => 1,
                    'description' => 'Superwall is a platform that helps you create and manage your own paywall.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'Anthropic',
                    'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Anthropic_logo.svg',
                    'llms_txt_url' => 'http://docs.anthropic.com/llms.txt',
                    'has_full' => 1,
                    'description' => 'Anthropic is an AI research company that focuses on developing more generalizable, interpretable, and steerable AI systems.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'Cursor',
                    'logo_url' => 'https://cursor.sh/cursor.svg',
                    'llms_txt_url' => 'https://docs.cursor.com/llms.txt',
                    'has_full' => 1,
                    'description' => 'Cursor is a platform that helps you create and manage your own knowledge graph.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'FastHTML',
                    'logo_url' => 'https://fastht.ml/logo.png',
                    'llms_txt_url' => 'https://docs.fastht.ml/llms.txt',
                    'has_full' => 0,
                    'description' => 'FastHTML is a fast and lightweight HTML parser.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'nbdev',
                    'logo_url' => 'https://nbdev.fast.ai/images/logo.png',
                    'llms_txt_url' => 'https://nbdev.fast.ai/llms.txt',
                    'has_full' => 1,
                    'description' => 'nbdev is a platform that helps you create and manage your own Jupyter notebooks.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'fastcore',
                    'logo_url' => 'https://fastcore.fast.ai/images/logo.png',
                    'llms_txt_url' => 'https://fastcore.fast.ai/llms.txt',
                    'has_full' => 1,
                    'description' => 'fastcore is a Python library that provides a set of tools for building and training neural networks.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ],
                [
                    'name' => 'Answer.AI',
                    'logo_url' => 'https://answer.ai/logo.png',
                    'llms_txt_url' => 'https://answer.ai/llms.txt',
                    'has_full' => 1,
                    'description' => 'Answer.AI is a platform that helps you create and manage your own conversational AI models.',
                    'is_requested' => 0,
                    'is_featured' => 1,
                    'votes' => 0
                ]
            ];

            // Add implementations to database if they don't exist
            foreach ($featuredImplementations as $impl) {
                $existingImpl = $db->getImplementationByUrl($impl['llms_txt_url']);
                if (!$existingImpl) {
                    $db->addImplementation($impl);
                }
            }
            ?>
            <div class="directory-section">
                <h2>Featured</h2>
                <div class="card-grid">
                    <?php
                    $implementations = $db->getFeaturedImplementations();
                    foreach ($implementations as $impl) {
                        $homepage = str_replace('/docs/llms.txt', '', $impl['llms_txt_url'] ?? '');
                        $homepage = str_replace('/llms.txt', '', $homepage);
                        
                        echo '<div class="card" onclick="window.location.href=\'' . htmlspecialchars($homepage) . '\'">
                                <svg class="external-link" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                                <div class="card-inner">
                                    <div class="card-content">
                                        <div class="title-row">
                                            <div class="logo-wrapper">';
                        if (!empty($impl['logo_url'])) {
                            echo '              <img src="' . htmlspecialchars($impl['logo_url']) . '" alt="' . htmlspecialchars($impl['name']) . ' logo">';
                        } else {
                            echo '              <div class="no-logo">' . htmlspecialchars(substr($impl['name'], 0, 1)) . '</div>';
                        }
                        echo '              </div>
                                            <div class="card-title">' . htmlspecialchars($impl['name']) . '</div>
                                        </div>
                                        <div class="description-wrapper">';
                        if (!empty($impl['description'])) {
                            echo '              <p class="card-description">' . htmlspecialchars($impl['description']) . '</p>';
                        } else {
                            echo '              <p class="card-description">&nbsp;</p>';
                        }
                        echo '              </div>
                                        <div class="card-labels">
                                            <a href="' . htmlspecialchars($impl['llms_txt_url'] ?? '#') . '" class="label label-txt" onclick="event.stopPropagation()">llms.txt</a>';
                        if ($impl['has_full']) {
                            echo '              <a href="' . str_replace('llms.txt', 'llms-full.txt', htmlspecialchars($impl['llms_txt_url'] ?? '')) . '" class="label label-full" onclick="event.stopPropagation()">llms-full.txt</a>';
                        }
                        echo '              </div>
                                    </div>
                                </div>
                            </div>';
                    }
                    ?>
                </div>
            </div>

            <hr class="section-divider" />

            <div class="directory-section">
                <h2>Why This Directory?</h2>
                <p>👋 Hi, I'm Szymon, the maker behind this website.</p>
                <p>Over the past several weeks, I've been transforming my life by using similar structured files to document my personal journey - it's been a complete game-changer.</p>
                <p>When I discovered on Friday evening that Anthropic had released their documentation in llms-full.txt format, I felt compelled to give back. Their Claude model has been invaluable to me, acting as both a therapist and productivity assistant.</p>
                <p>This website is my way of contributing to the community and helping popularize the llms.txt standard. It's actually my first web project since experiencing burnout 9 years ago, and I spent my entire weekend building it.</p>
                <h3>The directory serves to:</h3>
                <ul>
                    <li>Help developers discover AI-ready documentation</li>
                    <li>Showcase real-world implementations of the standard</li>
                    <li>Make it easier to verify which projects support AI-friendly docs</li>
                </ul>
                <p>I hope you find it useful! Feel free to reach out with any feedback or suggestions.</p>
            </div>

            <hr class="section-divider" />

            <div class="directory-section">
                <h2>Recently Added</h2>
                <div class="card-grid">
                    <?php
                    $recentImplementations = $db->getRecentlyAddedImplementations();
                    foreach ($recentImplementations as $impl) {
                        $homepage = str_replace('/docs/llms.txt', '', $impl['llms_txt_url'] ?? '');
                        $homepage = str_replace('/llms.txt', '', $homepage);
                        
                        echo '<div class="card" onclick="window.location.href=\'' . htmlspecialchars($homepage) . '\'">
                                <svg class="external-link" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                                <div class="card-inner">
                                    <div class="card-content">
                                        <div class="title-row">
                                            <div class="logo-wrapper">';
                        if (!empty($impl['logo_url'])) {
                            echo '              <img src="' . htmlspecialchars($impl['logo_url']) . '" alt="' . htmlspecialchars($impl['name']) . ' logo">';
                        } else {
                            echo '              <div class="no-logo">' . htmlspecialchars(substr($impl['name'], 0, 1)) . '</div>';
                        }
                        echo '              </div>
                                            <div class="card-title">' . htmlspecialchars($impl['name']) . '</div>
                                        </div>
                                        <div class="description-wrapper">';
                        if (!empty($impl['description'])) {
                            echo '              <p class="card-description">' . htmlspecialchars($impl['description']) . '</p>';
                        } else {
                            echo '              <p class="card-description">&nbsp;</p>';
                        }
                        echo '              </div>
                                        <div class="card-labels">
                                            <a href="' . htmlspecialchars($impl['llms_txt_url'] ?? '#') . '" class="label label-txt" onclick="event.stopPropagation()">llms.txt</a>';
                        if ($impl['has_full']) {
                            echo '              <a href="' . str_replace('llms.txt', 'llms-full.txt', htmlspecialchars($impl['llms_txt_url'] ?? '')) . '" class="label label-full" onclick="event.stopPropagation()">llms-full.txt</a>';
                        }
                        echo '              </div>
                                    </div>
                                </div>
                            </div>';
                    }
                    ?>
                </div>
            </div>

            <hr class="section-divider" />

            <div class="directory-section">
                <h2>Most Used</h2>
                <div class="card-grid">
                    <!-- Grid will be populated -->
                </div>
            </div>

            <div class="directory-section">
                <h2>Tools & SDKs</h2>
                <div class="card-grid">
                    <!-- Grid will be populated -->
                </div>
            </div>

            <div class="directory-section">
                <h2>Frameworks</h2>
                <div class="card-grid">
                    <!-- Grid will be populated -->
                </div>
            </div>

            <div class="directory-section">
                <h2>Infrastructure</h2>
                <div class="card-grid">
                    <!-- Grid will be populated -->
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <div>&copy; 2024 llmstxt.directory</div>
            <div class="footer-links">
                <a href="https://github.com/screenfluent/llmstxtdirectory">GitHub</a>
                <a href="https://llmstxt.org">llmstxt.org</a>
                <a href="https://github.com/jph00/llms-txt/issues">Feedback</a>
            </div>
        </div>
        <div class="footer">
            <p>Created by <a href="https://x.com/screenfluent" target="_blank">Szymon Rączka</a> • <a href="https://github.com/screenfluent/llmstxtdirectory" target="_blank">GitHub</a></p>
            <p class="version">Version 0.5.0 • Last updated: <?php echo date('F j, Y'); ?></p>
        </div>
    </footer>
    <?php
    logMemoryUsage();
    endRequestTiming($requestStart, $_SERVER['REQUEST_URI']);
    ?>
</body>
</html>
