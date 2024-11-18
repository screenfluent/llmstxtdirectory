<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../includes/LogAnalyzer.php';

requireAdminAuth();

$analyzer = new LogAnalyzer();
$stats = $analyzer->getPerformanceStats();
$recentMetrics = $analyzer->getRecentPerformanceMetrics(50);
$recentErrors = $analyzer->getRecentErrors(20);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Performance Metrics - llms.txt Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
            padding: 20px;
            background: #FFF;
            color: #333;
        }
        .metrics-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #FAFAFA;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
            padding: 20px;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #333;
        }
        .metric-value {
            font-size: 1.2em;
            font-weight: 500;
            margin: 5px 0;
        }
        .metric-label {
            color: #666;
            font-size: 0.9em;
        }
        .recent-list {
            background: #FAFAFA;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .recent-list h2 {
            margin-top: 0;
        }
        .metric-row {
            padding: 10px;
            border-bottom: 1px solid #E3E3E3;
        }
        .metric-row:last-child {
            border-bottom: none;
        }
        .error-row {
            background: #FFF5F5;
            padding: 10px;
            border-bottom: 1px solid #FFE5E5;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #E3E3E3;
        }
        .refresh-button {
            padding: 8px 16px;
            background: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <div class="metrics-container">
        <div class="nav-bar">
            <h1>Performance Metrics</h1>
            <a href="?refresh=true" class="refresh-button">Refresh</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Request Duration</h3>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['request_duration']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['request_duration']['count']; ?> requests)</div>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['request_duration']['p95']); ?></div>
                <div class="metric-label">95th Percentile</div>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['request_duration']['max']); ?></div>
                <div class="metric-label">Maximum</div>
            </div>

            <div class="stat-card">
                <h3>Database Queries</h3>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['db_query_duration']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['db_query_duration']['count']; ?> queries)</div>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['db_query_duration']['p95']); ?></div>
                <div class="metric-label">95th Percentile</div>
                <div class="metric-value"><?php echo $analyzer->formatDuration($stats['db_query_duration']['max']); ?></div>
                <div class="metric-label">Maximum</div>
            </div>

            <div class="stat-card">
                <h3>Memory Usage</h3>
                <div class="metric-value"><?php echo $analyzer->formatMemory($stats['memory_peak']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['memory_peak']['count']; ?> measurements)</div>
                <div class="metric-value"><?php echo $analyzer->formatMemory($stats['memory_peak']['max']); ?></div>
                <div class="metric-label">Peak</div>
            </div>
        </div>

        <div class="recent-list">
            <h2>Recent Performance Metrics</h2>
            <?php foreach ($recentMetrics as $metric): ?>
            <div class="metric-row">
                <strong><?php echo htmlspecialchars($metric['metric']); ?>:</strong>
                <?php
                    $value = $metric['metric'] === 'memory_peak' 
                        ? $analyzer->formatMemory($metric['value'])
                        : $analyzer->formatDuration($metric['value']);
                    echo $value;
                ?>
                <small style="color: #666;">
                    [<?php echo htmlspecialchars($metric['timestamp']); ?>]
                    <?php if (!empty($metric['tags'])): ?>
                        <?php foreach ($metric['tags'] as $key => $value): ?>
                            <?php echo htmlspecialchars($key) . ': ' . htmlspecialchars($value); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </small>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($recentErrors)): ?>
        <div class="recent-list">
            <h2>Recent Errors</h2>
            <?php foreach ($recentErrors as $error): ?>
            <div class="error-row">
                <strong>[<?php echo htmlspecialchars($error['timestamp']); ?>]</strong>
                <?php echo htmlspecialchars($error['error']); ?>
                <?php if (!empty($error['context'])): ?>
                    <br>
                    <small>
                        Context: <?php echo htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT)); ?>
                    </small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
