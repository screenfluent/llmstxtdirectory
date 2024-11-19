<?php
require_once __DIR__ . '/../../includes/environment.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../db/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$db = new Database();

// Initialize the database if needed
$db->initializeDatabase();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => $_POST['name'],
                    'slug' => strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['slug'])),
                    'description' => $_POST['description'],
                    'display_order' => (int)$_POST['display_order']
                ];
                
                try {
                    $db->addCategory($data);
                    $message = 'Category added successfully.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to add category: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $data = [
                    'name' => $_POST['name'],
                    'slug' => strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['slug'])),
                    'description' => $_POST['description'],
                    'display_order' => (int)$_POST['display_order']
                ];
                
                try {
                    $db->updateCategory($id, $data);
                    $message = 'Category updated successfully.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to update category: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                try {
                    $db->deleteCategory($id);
                    $message = 'Category deleted successfully.';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to delete category: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

$categories = $db->getCategories();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Categories - llms.txt Directory</title>
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
        .nav-links {
            display: flex;
            gap: 10px;
        }
        .nav-links a {
            padding: 8px 16px;
            border: 1px solid #E3E3E3;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
        .nav-links a.active {
            background: #333;
            color: #FFF;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .category-card {
            background: #FAFAFA;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
            padding: 20px;
        }
        .category-card h3 {
            margin-top: 0;
            color: #333;
        }
        .btn {
            padding: 8px 16px;
            border: 1px solid #E3E3E3;
            border-radius: 6px;
            background: #FFF;
            color: #333;
            cursor: pointer;
            font-family: 'Space Grotesk', sans-serif;
        }
        .btn:hover {
            background: #FAFAFA;
        }
        .btn.add-new {
            background: #333;
            color: #FFF;
        }
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #E8F5E9;
            color: #2E7D32;
        }
        .message.error {
            background: #FFEBEE;
            color: #C62828;
        }
        /* Keep existing modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .modal-close:hover,
        .modal-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="metrics-container">
        <div class="nav-bar">
            <h1>Categories</h1>
            <div class="nav-links">
                <a href="/admin/">Implementations</a>
                <a href="/admin/categories.php" class="active">Categories</a>
                <a href="/admin/metrics.php">Metrics</a>
                <a href="/admin/logout.php">Logout</a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="nav-bar">
            <h2>Manage Categories</h2>
            <button type="button" class="btn add-new" onclick="showAddModal()">Add New Category</button>
        </div>

        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                <div class="metric-value"><?php echo htmlspecialchars($category['slug']); ?></div>
                <div class="metric-label"><?php echo htmlspecialchars($category['description']); ?></div>
                <div style="margin-top: 15px;">
                    <button class="btn" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">Edit</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category? All implementations in this category will have their category removed.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                        <button type="submit" class="btn">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Add/Edit Modal -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <h2 id="modalTitle">Add New Category</h2>
                <span class="modal-close">&times;</span>
                <form id="categoryForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="edit-id" value="">

                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug:</label>
                        <input type="text" id="slug" name="slug" required pattern="[a-zA-Z0-9-]+" title="Only letters, numbers, and hyphens allowed">
                        <small class="hint">Used in URLs and code. Only letters, numbers, and hyphens allowed.</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <input type="text" id="description" name="description">
                        <small class="hint">Brief description of what this category represents.</small>
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order:</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                        <small class="hint">Lower numbers appear first. Categories with the same order are sorted alphabetically.</small>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn add-new">Save Changes</button>
                        <button type="button" class="btn cancel" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function showAddModal() {
                document.getElementById('modalTitle').textContent = 'Add New Category';
                document.getElementById('formAction').value = 'add';
                document.getElementById('edit-id').value = '';
                document.getElementById('name').value = '';
                document.getElementById('slug').value = '';
                document.getElementById('description').value = '';
                document.getElementById('display_order').value = '0';
                document.getElementById('modal').style.display = 'block';
            }

            function showEditModal(category) {
                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('edit-id').value = category.id;
                
                document.getElementById('name').value = category.name;
                document.getElementById('slug').value = category.slug;
                document.getElementById('description').value = category.description;
                document.getElementById('display_order').value = category.display_order;
                
                document.getElementById('modal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('modal').style.display = 'none';
            }

            // Auto-generate slug from name
            document.getElementById('name').addEventListener('input', function() {
                const slugInput = document.getElementById('slug');
                if (!slugInput.value || !slugInput._userModified) {
                    slugInput.value = this.value.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            });

            // Track if user has modified the slug
            document.getElementById('slug').addEventListener('input', function() {
                this._userModified = true;
            });

            // Event listeners
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.modal-close').addEventListener('click', closeModal);
                
                // Close modal when clicking outside
                document.getElementById('modal').addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });
            });
            
            // Keep modal open if there was an error
            <?php if ($messageType === 'error' && isset($_POST['action'])): ?>
            document.getElementById('modal').style.display = 'block';
            <?php endif; ?>
        </script>
    </div>
</body>
</html>
