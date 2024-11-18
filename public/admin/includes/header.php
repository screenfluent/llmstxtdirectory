<?php
if (!defined('ADMIN_PAGE')) {
    exit('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - llms.txt Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <?php if (isProduction()): ?>
    <script
        src="https://beamanalytics.b-cdn.net/beam.min.js"
        data-token="93f53d9b-fadc-433a-9c7c-9621ac1ee672"
        async
    ></script>
    <?php endif; ?>
    <?php if (isset($extraHead)) echo $extraHead; ?>
