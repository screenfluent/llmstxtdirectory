<?php
require_once __DIR__ . "/../../includes/environment.php";
require_once __DIR__ . "/../../includes/admin_auth.php";
require_once __DIR__ . "/../../db/database.php";
require_once __DIR__ . "/../../includes/helpers.php";

// Require authentication
requireAdminAuth();

$db = new Database();

// Initialize message variables
$message = "";
$messageType = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "approve":
                try {
                    $submissionId = $_POST["submission_id"];
                    $submission = $db->getSubmissionById($submissionId);
                    
                    if ($submission) {
                        // Add to implementations
                        $implData = [
                            "name" => $_POST["name"],
                            "description" => $_POST["description"] ?? "",
                            "llms_txt_url" => $submission["url"],
                            "has_full" => isset($_POST["has_full"]) ? 1 : 0,
                            "is_featured" => 0,
                            "is_draft" => 0
                        ];
                        
                        if ($db->addImplementation($implData)) {
                            // Update submission status
                            $db->updateSubmissionStatus($submissionId, "approved");
                            $message = "Submission approved and added to implementations.";
                            $messageType = "success";
                        } else {
                            throw new Exception("Failed to add implementation");
                        }
                    }
                } catch (Exception $e) {
                    $message = "Error approving submission: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

            case "reject":
                try {
                    $submissionId = $_POST["submission_id"];
                    if ($db->updateSubmissionStatus($submissionId, "rejected")) {
                        $message = "Submission rejected.";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to update submission status");
                    }
                } catch (Exception $e) {
                    $message = "Error rejecting submission: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    }
}

// Get all submissions
$submissions = $db->getSubmissions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin - Submissions - llms.txt Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        .submissions {
            margin-top: 20px;
        }
        .submission {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .submission-url {
            font-weight: 500;
            font-size: 1.1em;
        }
        .submission-meta {
            color: #666;
            font-size: 0.9em;
        }
        .submission-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Submissions</h1>
            <div class="admin-nav">
                <a href="/admin" class="nav-link">Dashboard</a>
                <a href="/admin/submissions.php" class="nav-link active">Submissions</a>
                <a href="/admin/categories.php" class="nav-link">Categories</a>
                <a href="/admin/metrics.php" class="nav-link">Metrics</a>
                <a href="/admin/logout.php" class="nav-link">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="submissions">
            <?php if (empty($submissions)): ?>
                <p>No submissions found.</p>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission">
                        <div class="submission-header">
                            <div class="submission-url">
                                <a href="<?php echo htmlspecialchars($submission['url']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($submission['url']); ?>
                                </a>
                            </div>
                            <div class="status-badge status-<?php echo htmlspecialchars($submission['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($submission['status'])); ?>
                            </div>
                        </div>
                        
                        <div class="submission-meta">
                            Submitted: <?php echo htmlspecialchars($submission['submitted_at']); ?>
                            <?php if ($submission['email']): ?>
                                | Email: <?php echo htmlspecialchars($submission['email']); ?>
                            <?php endif; ?>
                            | IP: <?php echo htmlspecialchars($submission['ip_address']); ?>
                        </div>

                        <?php if ($submission['status'] === 'pending'): ?>
                            <div class="submission-actions">
                                <button onclick="showApproveModal(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['url'], ENT_QUOTES); ?>')" class="button button-success">
                                    Approve
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit" class="button button-danger" onclick="return confirm('Are you sure you want to reject this submission?')">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <h2>Approve Submission</h2>
            <form id="approveForm" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="submission_id" id="approveSubmissionId">
                
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="has_full">
                        Has llms-full.txt
                    </label>
                </div>

                <div class="form-group">
                    <label for="url">URL:</label>
                    <input type="text" id="approveUrl" readonly>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-success">Approve</button>
                    <button type="button" class="button" onclick="closeApproveModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showApproveModal(submissionId, url) {
            document.getElementById('approveSubmissionId').value = submissionId;
            document.getElementById('approveUrl').value = url;
            document.getElementById('approveModal').style.display = 'block';
            
            // Extract name from URL
            const urlObj = new URL(url);
            const host = urlObj.hostname.replace('www.', '');
            document.getElementById('name').value = host.split('.')[0].charAt(0).toUpperCase() + host.split('.')[0].slice(1);
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
            document.getElementById('approveForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('approveModal');
            if (event.target === modal) {
                closeApproveModal();
            }
        }
    </script>
</body>
</html>
