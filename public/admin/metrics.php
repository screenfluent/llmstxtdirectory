<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../includes/LogAnalyzer.php';
require_once __DIR__ . '/../../includes/monitoring.php';

requireAdminAuth();

$analyzer = new LogAnalyzer();

// Get time range from query parameters
$timeRange = isset($_GET['range']) ? intval($_GET['range']) : 3600; // Default to 1 hour
$stats = $analyzer->getPerformanceStats($timeRange);
$recentMetrics = $analyzer->getRecentPerformanceMetrics(50, $timeRange);
$recentErrors = $analyzer->getRecentErrors(20, $timeRange);

// Clean old logs
$analyzer->cleanOldLogs();

function formatDuration($ms) {
    return number_format($ms, 2) . 'ms';
}

function formatMemory($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getTimeRangeLabel($seconds) {
    if ($seconds < 3600) return (int)($seconds / 60) . ' minutes';
    if ($seconds < 86400) return (int)($seconds / 3600) . ' hours';
    return (int)($seconds / 86400) . ' days';
}
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
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .time-range {
            display: flex;
            gap: 10px;
        }
        .time-range a {
            padding: 8px 16px;
            border: 1px solid #E3E3E3;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
        .time-range a.active {
            background: #333;
            color: #FFF;
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
        .route-stats {
            margin-top: 30px;
        }
        .route-card {
            background: #FFF;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .route-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .route-metric {
            padding: 10px;
            background: #F5F5F5;
            border-radius: 6px;
        }
        .errors-section {
            margin-top: 30px;
        }
        .error-card {
            background: #FFF;
            border: 1px solid #FFE4E4;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .error-time {
            color: #666;
            font-size: 0.9em;
        }
        .error-message {
            margin: 10px 0;
            font-weight: 500;
        }
        .error-context {
            font-family: monospace;
            background: #F5F5F5;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.9em;
            white-space: pre-wrap;
        }
        .debug-info {
            background: #F5F5F5;
            padding: 20px;
            margin-bottom: 20px;
            font-family: monospace;
            white-space: pre;
        }
    </style>
</head>
<body>
    <?php $requestStart = startRequestTiming(); ?>
    <div class="metrics-container">
        <?php if (isset($_GET['debug'])): ?>
        <div class="debug-info">
            <?php
            $analyzer->debugInspectLogs();
            echo "Raw metrics:\n";
            print_r($recentMetrics);
            echo "\nProcessed stats:\n";
            print_r($stats);
            ?>
        </div>
        <?php endif; ?>
        <div class="nav-bar">
            <h1>Performance Metrics</h1>
            <div class="time-range">
                <a href="?range=900" <?php echo $timeRange == 900 ? 'class="active"' : ''; ?>>15m</a>
                <a href="?range=3600" <?php echo $timeRange == 3600 ? 'class="active"' : ''; ?>>1h</a>
                <a href="?range=86400" <?php echo $timeRange == 86400 ? 'class="active"' : ''; ?>>24h</a>
                <a href="?range=604800" <?php echo $timeRange == 604800 ? 'class="active"' : ''; ?>>7d</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Request Duration</h3>
                <div class="metric-value"><?php echo formatDuration($stats['request_duration']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['request_duration']['count']; ?> requests)</div>
                <div class="metric-value"><?php echo formatDuration($stats['request_duration']['p95']); ?></div>
                <div class="metric-label">95th Percentile</div>
                <div class="metric-value"><?php echo formatDuration($stats['request_duration']['p99']); ?></div>
                <div class="metric-label">99th Percentile</div>
                <div class="metric-value"><?php echo formatDuration($stats['request_duration']['max']); ?></div>
                <div class="metric-label">Maximum</div>
            </div>

            <div class="stat-card">
                <h3>Database Queries</h3>
                <div class="metric-value"><?php echo formatDuration($stats['db_query_duration']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['db_query_duration']['count']; ?> queries)</div>
                <div class="metric-value"><?php echo formatDuration($stats['db_query_duration']['p95']); ?></div>
                <div class="metric-label">95th Percentile</div>
                <div class="metric-value"><?php echo formatDuration($stats['db_query_duration']['p99']); ?></div>
                <div class="metric-label">99th Percentile</div>
                <div class="metric-value"><?php echo formatDuration($stats['db_query_duration']['max']); ?></div>
                <div class="metric-label">Maximum</div>
            </div>

            <div class="stat-card">
                <h3>Memory Usage</h3>
                <div class="metric-value"><?php echo formatMemory($stats['memory_peak']['avg']); ?></div>
                <div class="metric-label">Average (<?php echo $stats['memory_peak']['count']; ?> measurements)</div>
                <div class="metric-value"><?php echo formatMemory($stats['memory_peak']['max']); ?></div>
                <div class="metric-label">Peak</div>
            </div>
        </div>

        <div class="route-stats">
            <h2>Route Performance</h2>
            <?php foreach ($stats['routes'] as $route => $routeStats): ?>
            <div class="route-card">
                <div class="route-header">
                    <h3><?php echo htmlspecialchars($route); ?></h3>
                    <span><?php echo $routeStats['count']; ?> requests</span>
                </div>
                <div class="route-metrics">
                    <div class="route-metric">
                        <div class="metric-label">Average</div>
                        <div class="metric-value"><?php echo formatDuration($routeStats['avg']); ?></div>
                    </div>
                    <div class="route-metric">
                        <div class="metric-label">Median</div>
                        <div class="metric-value"><?php echo formatDuration($routeStats['median']); ?></div>
                    </div>
                    <div class="route-metric">
                        <div class="metric-label">95th Percentile</div>
                        <div class="metric-value"><?php echo formatDuration($routeStats['p95']); ?></div>
                    </div>
                    <div class="route-metric">
                        <div class="metric-label">Standard Deviation</div>
                        <div class="metric-value"><?php echo formatDuration($routeStats['std_dev']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="errors-section">
            <h2>Recent Errors</h2>
            <?php foreach ($recentErrors as $error): ?>
            <div class="error-card">
                <div class="error-time"><?php echo $error['timestamp']; ?></div>
                <div class="error-message"><?php echo htmlspecialchars($error['error']); ?></div>
                <?php if (!empty($error['context'])): ?>
                <div class="error-context"><?php echo htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT)); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    logMemoryUsage();
    endRequestTiming($requestStart, '/admin/metrics');
    ?>
</body>
</html>
