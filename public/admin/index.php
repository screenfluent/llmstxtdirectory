<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../db/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ImageOptimizer.php';

// Require authentication
requireAdminAuth();

$db = new Database();
$imageOptimizer = new ImageOptimizer('public/logos');

// Initialize message variables
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'llms_txt_url' => $_POST['llms_txt_url'],
                    'has_full' => isset($_POST['has_full']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'is_draft' => isset($_POST['is_draft']) ? 1 : 0,
                    'is_requested' => isset($_POST['is_requested']) ? 1 : 0,
                    'votes' => $_POST['votes'] ?? 0
                ];
                
                // Check for duplicate URL before upload handling
                $existing = $db->getImplementationByUrl($data['llms_txt_url']);
                if ($existing) {
                    $message = "An implementation with this llms.txt URL already exists.";
                    $messageType = 'error';
                    break;
                }
                
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Debug information
                    logError('Logo upload attempt', [
                        'file_info' => [
                            'name' => $file['name'],
                            'type' => $file['type'],
                            'tmp_name' => $file['tmp_name'],
                            'error' => $file['error'],
                            'size' => $file['size']
                        ],
                        'extension' => $ext,
                        'upload_dir' => __DIR__ . '/../../public/logos',
                        'server_info' => [
                            'script_path' => __FILE__,
                            'document_root' => $_SERVER['DOCUMENT_ROOT'],
                            'php_version' => PHP_VERSION,
                            'os' => PHP_OS
                        ]
                    ]);
                    
                    // Validate file type
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                        $result = $imageOptimizer->processUploadedImage($file, $_POST['name']);
                        if ($result['success']) {
                            $data['logo_url'] = '/logos/' . $result['filename'];
                            logError('Logo processed successfully', [
                                'result' => $result,
                                'logo_url' => $data['logo_url']
                            ]);
                        } else {
                            logError('Logo processing failed', [
                                'result' => $result
                            ]);
                            $message = "Failed to process logo image. " . ($result['error'] ?? 'Unknown error');
                            $messageType = 'error';
                            break;
                        }
                    } elseif ($ext === 'svg') {
                        // Handle SVG files separately (no optimization needed)
                        $filename = get_logo_filename($_POST['name']) . '.svg';
                        $logos_dir = realpath(__DIR__ . '/../../public/logos');
                        if ($logos_dir === false) {
                            $logos_dir = __DIR__ . '/../../public/logos';
                        }
                        $target_path = $logos_dir . '/' . $filename;
                        
                        logError('SVG upload attempt', [
                            'paths' => [
                                'logos_dir' => $logos_dir,
                                'target_path' => $target_path,
                                'base_dir' => __DIR__,
                                'resolved_path' => realpath($target_path)
                            ]
                        ]);
                        
                        // Create logos directory if it doesn't exist
                        if (!is_dir($logos_dir)) {
                            if (!mkdir($logos_dir, 0775, true)) {
                                logError('Failed to create logos directory', [
                                    'path' => $logos_dir,
                                    'error' => error_get_last()
                                ]);
                                $message = "Failed to create logos directory. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                            chmod($logos_dir, 0775);
                            logError('Created logos directory', [
                                'path' => $logos_dir,
                                'permissions' => decoct(fileperms($logos_dir) & 0777)
                            ]);
                        }
                        
                        // Check directory permissions
                        if (!is_writable($logos_dir)) {
                            chmod($logos_dir, 0775);
                            logError('Updated logos directory permissions', [
                                'path' => $logos_dir,
                                'permissions' => decoct(fileperms($logos_dir) & 0777),
                                'is_writable' => is_writable($logos_dir)
                            ]);
                            if (!is_writable($logos_dir)) {
                                logError('Logos directory still not writable', [
                                    'path' => $logos_dir,
                                    'permissions' => decoct(fileperms($logos_dir) & 0777),
                                    'owner' => fileowner($logos_dir),
                                    'group' => filegroup($logos_dir)
                                ]);
                                $message = "Logos directory is not writable. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                        }
                        
                        // Delete old file if it exists
                        if (file_exists($target_path)) {
                            if (!is_writable($target_path)) {
                                chmod($target_path, 0664);
                                logError('Updated existing file permissions', [
                                    'path' => $target_path,
                                    'permissions' => decoct(fileperms($target_path) & 0777)
                                ]);
                            }
                            if (!unlink($target_path)) {
                                logError('Failed to delete existing SVG file', [
                                    'path' => $target_path,
                                    'error' => error_get_last(),
                                    'permissions' => decoct(fileperms($target_path) & 0777)
                                ]);
                                $message = "Failed to delete existing logo file. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                            logError('Deleted existing file', [
                                'path' => $target_path
                            ]);
                        }
                        
                        // Try to move the uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                            $moveError = error_get_last();
                            logError('Failed to move uploaded SVG file', [
                                'source' => $file['tmp_name'],
                                'target' => $target_path,
                                'error' => $moveError,
                                'upload_error' => $file['error'],
                                'tmp_file_exists' => file_exists($file['tmp_name']),
                                'tmp_file_readable' => is_readable($file['tmp_name']),
                                'target_dir_writable' => is_writable(dirname($target_path))
                            ]);
                            $message = "Failed to save logo file. Error: " . ($moveError['message'] ?? 'Unknown error');
                            $messageType = 'error';
                            break;
                        }
                        
                        // Set file permissions
                        if (!chmod($target_path, 0664)) {
                            logError('Failed to set SVG file permissions', [
                                'path' => $target_path,
                                'error' => error_get_last(),
                                'current_perms' => decoct(fileperms($target_path) & 0777)
                            ]);
                            $message = "Failed to set file permissions. Please check server configuration.";
                            $messageType = 'error';
                            break;
                        }
                        
                        $data['logo_url'] = '/logos/' . $filename;
                        logError('SVG file uploaded successfully', [
                            'path' => $target_path,
                            'url' => $data['logo_url'],
                            'permissions' => decoct(fileperms($target_path) & 0777)
                        ]);
                    }
                }
                
                if ($db->addImplementation($data)) {
                    $message = "Implementation added successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Failed to add implementation. Please try again.";
                    $messageType = 'error';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'llms_txt_url' => $_POST['llms_txt_url'],
                    'has_full' => isset($_POST['has_full']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'is_draft' => isset($_POST['is_draft']) ? 1 : 0,
                    'is_requested' => isset($_POST['is_requested']) ? 1 : 0,
                    'votes' => $_POST['votes'] ?? 0
                ];
                
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Debug information
                    logError('Logo upload attempt', [
                        'file_info' => [
                            'name' => $file['name'],
                            'type' => $file['type'],
                            'tmp_name' => $file['tmp_name'],
                            'error' => $file['error'],
                            'size' => $file['size']
                        ],
                        'extension' => $ext,
                        'upload_dir' => __DIR__ . '/../../public/logos',
                        'server_info' => [
                            'script_path' => __FILE__,
                            'document_root' => $_SERVER['DOCUMENT_ROOT'],
                            'php_version' => PHP_VERSION,
                            'os' => PHP_OS
                        ]
                    ]);
                    
                    // Validate file type
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                        $result = $imageOptimizer->processUploadedImage($file, $_POST['name']);
                        if ($result['success']) {
                            $data['logo_url'] = '/logos/' . $result['filename'];
                            logError('Logo processed successfully', [
                                'result' => $result,
                                'logo_url' => $data['logo_url']
                            ]);
                        } else {
                            logError('Logo processing failed', [
                                'result' => $result
                            ]);
                            $message = "Failed to process logo image. " . ($result['error'] ?? 'Unknown error');
                            $messageType = 'error';
                            break;
                        }
                    } elseif ($ext === 'svg') {
                        // Handle SVG files separately (no optimization needed)
                        $filename = get_logo_filename($_POST['name']) . '.svg';
                        $logos_dir = realpath(__DIR__ . '/../../public/logos');
                        if ($logos_dir === false) {
                            $logos_dir = __DIR__ . '/../../public/logos';
                        }
                        $target_path = $logos_dir . '/' . $filename;
                        
                        logError('SVG upload attempt', [
                            'paths' => [
                                'logos_dir' => $logos_dir,
                                'target_path' => $target_path,
                                'base_dir' => __DIR__,
                                'resolved_path' => realpath($target_path)
                            ]
                        ]);
                        
                        // Create logos directory if it doesn't exist
                        if (!is_dir($logos_dir)) {
                            if (!mkdir($logos_dir, 0775, true)) {
                                logError('Failed to create logos directory', [
                                    'path' => $logos_dir,
                                    'error' => error_get_last()
                                ]);
                                $message = "Failed to create logos directory. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                            chmod($logos_dir, 0775);
                            logError('Created logos directory', [
                                'path' => $logos_dir,
                                'permissions' => decoct(fileperms($logos_dir) & 0777)
                            ]);
                        }
                        
                        // Check directory permissions
                        if (!is_writable($logos_dir)) {
                            chmod($logos_dir, 0775);
                            logError('Updated logos directory permissions', [
                                'path' => $logos_dir,
                                'permissions' => decoct(fileperms($logos_dir) & 0777),
                                'is_writable' => is_writable($logos_dir)
                            ]);
                            if (!is_writable($logos_dir)) {
                                logError('Logos directory still not writable', [
                                    'path' => $logos_dir,
                                    'permissions' => decoct(fileperms($logos_dir) & 0777),
                                    'owner' => fileowner($logos_dir),
                                    'group' => filegroup($logos_dir)
                                ]);
                                $message = "Logos directory is not writable. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                        }
                        
                        // Delete old file if it exists
                        if (file_exists($target_path)) {
                            if (!is_writable($target_path)) {
                                chmod($target_path, 0664);
                                logError('Updated existing file permissions', [
                                    'path' => $target_path,
                                    'permissions' => decoct(fileperms($target_path) & 0777)
                                ]);
                            }
                            if (!unlink($target_path)) {
                                logError('Failed to delete existing SVG file', [
                                    'path' => $target_path,
                                    'error' => error_get_last(),
                                    'permissions' => decoct(fileperms($target_path) & 0777)
                                ]);
                                $message = "Failed to delete existing logo file. Please check permissions.";
                                $messageType = 'error';
                                break;
                            }
                            logError('Deleted existing file', [
                                'path' => $target_path
                            ]);
                        }
                        
                        // Try to move the uploaded file
                        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                            $moveError = error_get_last();
                            logError('Failed to move uploaded SVG file', [
                                'source' => $file['tmp_name'],
                                'target' => $target_path,
                                'error' => $moveError,
                                'upload_error' => $file['error'],
                                'tmp_file_exists' => file_exists($file['tmp_name']),
                                'tmp_file_readable' => is_readable($file['tmp_name']),
                                'target_dir_writable' => is_writable(dirname($target_path))
                            ]);
                            $message = "Failed to save logo file. Error: " . ($moveError['message'] ?? 'Unknown error');
                            $messageType = 'error';
                            break;
                        }
                        
                        // Set file permissions
                        if (!chmod($target_path, 0664)) {
                            logError('Failed to set SVG file permissions', [
                                'path' => $target_path,
                                'error' => error_get_last(),
                                'current_perms' => decoct(fileperms($target_path) & 0777)
                            ]);
                            $message = "Failed to set file permissions. Please check server configuration.";
                            $messageType = 'error';
                            break;
                        }
                        
                        $data['logo_url'] = '/logos/' . $filename;
                        logError('SVG file uploaded successfully', [
                            'path' => $target_path,
                            'url' => $data['logo_url'],
                            'permissions' => decoct(fileperms($target_path) & 0777)
                        ]);
                    }
                }
                
                if ($db->updateImplementation($id, $data)) {
                    $message = "Implementation updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Failed to update implementation. Please try again.";
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                if ($db->deleteImplementation($_POST['id'])) {
                    $message = "Implementation deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Failed to delete implementation. Please try again.";
                    $messageType = 'error';
                }
                break;
        }
    }
}

$implementations = $db->getImplementations(true); // Pass true to show all implementations including drafts
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - llms.txt Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
            padding: 20px;
            background: #FFF;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #E3E3E3;
        }
        .admin-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .nav-link {
            padding: 8px 16px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .nav-link:hover {
            background-color: #f5f5f5;
        }
        .nav-link.active {
            background-color: #333;
            color: white;
        }
        .add-new, button {
            padding: 8px 16px;
            background: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
        }
        .logout-btn {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 20px;
        }
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
            background: #FAFAFA;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #FAFAFA;
            table-layout: fixed;
            margin: 0;
        }
        th {
            background: #F5F5F5;
            position: sticky;
            top: 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E3E3E3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        td.description {
            white-space: normal;
            max-width: 200px;
        }
        td.url {
            max-width: 150px;
        }
        td.name {
            max-width: 150px;
        }
        td.logo {
            max-width: 150px;
        }
        td.status {
            width: 80px;
        }
        td.votes {
            width: 60px;
        }
        td.actions {
            width: 120px;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            margin: 0 4px;
        }
        .btn-edit {
            background: #333;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #bb2d3b;
        }
        .actions form {
            display: inline-block;
            margin: 0;
            padding: 0;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .modal-content h2 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #E3E3E3;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px dashed #E3E3E3;
            border-radius: 6px;
            cursor: pointer;
        }
        input[type="file"]:hover {
            border-color: #333;
        }
        .hint {
            margin: 4px 0 0;
            font-size: 12px;
            color: #666;
        }
        .logo-preview {
            margin-top: 8px;
            width: 32px;
            height: 32px;
            border: 1px solid #E3E3E3;
            border-radius: 4px;
            overflow: hidden;
            display: none;
        }
        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E3E3E3;
        }
        .modal-actions .btn {
            padding: 8px 16px;
            font-size: 14px;
        }
        .modal-actions .btn.add-new {
            background: #333;
        }
        table td {
            font-size: 14px;
            vertical-align: middle;
        }
        table th {
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
        }
        .actions {
            white-space: nowrap;
        }
        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: 500;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-nav">
                <a href="/admin/" class="nav-link active">Manage Implementations</a>
                <a href="/admin/metrics.php" class="nav-link">Performance Metrics</a>
                <a href="/admin/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="implementations-section">
            <div class="section-header">
                <h2>Manage Implementations</h2>
                <button class="add-new" onclick="showAddModal()">Add New</button>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="name">Name</th>
                            <th class="logo">Logo URL</th>
                            <th class="description">Description</th>
                            <th class="url">llms.txt URL</th>
                            <th class="status">Has Full</th>
                            <th class="status">Is Featured</th>
                            <th class="status">Is Draft</th>
                            <th class="status">Type</th>
                            <th class="votes">Votes</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($implementations as $impl): ?>
                        <tr>
                            <td class="name"><?= htmlspecialchars($impl['name']) ?></td>
                            <td class="logo"><?= htmlspecialchars($impl['logo_url'] ?? '') ?></td>
                            <td class="description"><?= htmlspecialchars($impl['description'] ?? '') ?></td>
                            <td class="url"><?= htmlspecialchars($impl['llms_txt_url'] ?? '') ?></td>
                            <td class="status"><?= $impl['has_full'] ? 'Yes' : 'No' ?></td>
                            <td class="status"><?= $impl['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td class="status"><?= $impl['is_draft'] ? 'Yes' : 'No' ?></td>
                            <td class="status"><?= $impl['is_requested'] ? 'Requested' : 'Regular' ?></td>
                            <td class="votes"><?= htmlspecialchars((string)($impl['votes'] ?? 0)) ?></td>
                            <td class="actions">
                                <button class="btn btn-edit" onclick="showEditModal(<?= htmlspecialchars(json_encode($impl)) ?>)">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this implementation?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($impl['id']) ?>">
                                    <button type="submit" class="btn btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Implementation</h2>
            <form id="implementationForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="edit-id" value="">

                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="llms_txt_url">llms.txt URL:</label>
                    <input type="url" id="llms_txt_url" name="llms_txt_url" required>
                </div>

                <div class="form-group">
                    <label for="logo">Logo:</label>
                    <input type="file" id="logo" name="logo" accept=".svg,.png,.jpg,.jpeg">
                    <div class="logo-preview" style="display: none;">
                        <img src="" alt="Logo preview">
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="has_full" name="has_full" value="1">
                        Has Full Implementation
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1">
                        Featured
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_draft" name="is_draft" value="1">
                        Draft
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_requested" name="is_requested" value="1">
                        Is Requested Implementation
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn add-new">Save</button>
                    <button type="button" class="btn cancel" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Implementation';
            document.getElementById('formAction').value = 'add';
            document.getElementById('edit-id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('llms_txt_url').value = '';
            document.getElementById('has_full').checked = false;
            document.getElementById('is_featured').checked = false;
            document.getElementById('is_draft').checked = false;
            document.getElementById('is_requested').checked = false;
            document.querySelector('.logo-preview').style.display = 'none';
            document.getElementById('modal').style.display = 'flex';
        }

        function showEditModal(impl) {
            document.getElementById('modalTitle').textContent = 'Edit Implementation';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('edit-id').value = impl.id;
            document.getElementById('name').value = impl.name || '';
            document.getElementById('description').value = impl.description || '';
            document.getElementById('llms_txt_url').value = impl.llms_txt_url || '';
            document.getElementById('has_full').checked = impl.has_full === 1;
            document.getElementById('is_featured').checked = impl.is_featured === 1;
            document.getElementById('is_draft').checked = impl.is_draft === 1;
            document.getElementById('is_requested').checked = impl.is_requested === 1;
            
            const preview = document.querySelector('.logo-preview');
            if (impl.logo_url) {
                preview.style.display = 'block';
                preview.querySelector('img').src = impl.logo_url;
            } else {
                preview.style.display = 'none';
            }
            
            document.getElementById('modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        // Keep modal open if there was an error
        <?php if ($messageType === 'error' && isset($_POST['action'])): ?>
        document.getElementById('modal').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>
