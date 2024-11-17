<?php
session_start();

require_once __DIR__ . '/../../db/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Basic authentication
$admin_username = 'admin';
$admin_password = 'change_this_password'; // Change this to a secure password

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        isset($_POST['username']) && 
        isset($_POST['password'])) {
        
        if ($_POST['username'] === $admin_username && 
            $_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $error = "Invalid credentials";
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - llms.txt Directory</title>
            <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Space Grotesk', sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #FFF;
                }
                .login-container {
                    max-width: 400px;
                    margin: 40px auto;
                    padding: 20px;
                    background: #FAFAFA;
                    border: 1px solid #E3E3E3;
                    border-radius: 10px;
                }
                h1 {
                    text-align: center;
                    color: #333;
                    margin-bottom: 20px;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    color: #333;
                }
                input[type="text"],
                input[type="password"] {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #E3E3E3;
                    border-radius: 4px;
                    font-family: inherit;
                }
                button {
                    width: 100%;
                    padding: 10px;
                    background: #333;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-family: inherit;
                }
                .error {
                    color: #dc3545;
                    margin-bottom: 15px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>Admin Login</h1>
                <?php if (isset($error)) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$db = new Database();

// Handle form submissions
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
                    'is_requested' => isset($_POST['is_requested']) ? 1 : 0
                ];
                
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Validate file type
                    if (in_array($ext, ['svg', 'png', 'jpg', 'jpeg'])) {
                        $filename = get_logo_filename($_POST['name']) . '.' . $ext;
                        $target_path = __DIR__ . '/../logos/' . $filename;
                        
                        // Create logos directory if it doesn't exist
                        if (!is_dir(__DIR__ . '/../logos')) {
                            mkdir(__DIR__ . '/../logos', 0775, true);
                        }
                        
                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            chmod($target_path, 0664);
                            $data['logo_url'] = '/logos/' . $filename;
                        }
                    }
                }
                
                $db->addImplementation($data);
                break;
                
            case 'edit':
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'llms_txt_url' => $_POST['llms_txt_url'],
                    'has_full' => isset($_POST['has_full']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'is_draft' => isset($_POST['is_draft']) ? 1 : 0,
                    'is_requested' => isset($_POST['is_requested']) ? 1 : 0
                ];
                
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['logo'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    // Validate file type
                    if (in_array($ext, ['svg', 'png', 'jpg', 'jpeg'])) {
                        $filename = get_logo_filename($_POST['name']) . '.' . $ext;
                        $target_path = __DIR__ . '/../logos/' . $filename;
                        
                        // Create logos directory if it doesn't exist
                        if (!is_dir(__DIR__ . '/../logos')) {
                            mkdir(__DIR__ . '/../logos', 0775, true);
                        }
                        
                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            chmod($target_path, 0664);
                            $data['logo_url'] = '/logos/' . $filename;
                        }
                    }
                }
                
                $db->updateImplementation($_POST['id'], $data);
                break;
                
            case 'delete':
                $db->deleteImplementation($_POST['id']);
                break;
        }
    }
}

$implementations = $db->getImplementations();
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
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .add-new {
            padding: 8px 16px;
            background: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
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
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-edit {
            background: #ffd700;
            color: #333;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Manage Implementations</h1>
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
    </script>
</body>
</html>
