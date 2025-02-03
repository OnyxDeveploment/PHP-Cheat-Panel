<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Get current software status from `settings` table
$statusStmt = $conn->prepare("SELECT value FROM settings WHERE name = 'status'");
$statusStmt->execute();
$status = $statusStmt->fetchColumn() ?? 'Offline';


$usedKeysStmt = $conn->query("SELECT COUNT(*) FROM license_keys WHERE is_used = 1");
$totalUsedKeys = $usedKeysStmt->fetchColumn();

// Predefined key formats for dropdown
$keyFormats = [
    "xxxx-xxxx-xxxx"      => "XXXX-XXXX-XXXX",
    "xxxx-xxxx"           => "XXXX-XXXX",
    "xxxx-xxxx-xxxx-xxxx" => "XXXX-XXXX-XXXX-XXXX",
    "xx-xx-xx"            => "XX-XX-XX",
    "xxx-xxx"             => "XXX-XXX",
    "custom"              => "Custom Format (Enter Below)",
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updateSoftwareStatus'])) {
        handleStatusUpdate();
    } elseif (isset($_POST['updateLicenseStatus'])) {
        handleLicenseStatusUpdate();
    } elseif (isset($_POST['deleteKey'])) {
        handleKeyDeletion();
    } elseif (isset($_POST['addChangelog'])) {
        handleChangelogOperation('add');
    } elseif (isset($_POST['editChangelog'])) {
        handleChangelogOperation('edit');
    } elseif (isset($_POST['deleteChangelog'])) {
        handleChangelogOperation('delete');
    }
}

// Helper functions
function setToastMessage($message, $type) {
    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_type'] = $type;
}

function redirect() {
    header("Location: ./index.php");
    exit;
}

function handleStatusUpdate() {
    global $conn;
    $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'status'");
    if ($stmt->execute([$_POST['newStatus']])) {
        setToastMessage("Software status updated!", "success");
    } else {
        setToastMessage("Error updating software status!", "error");
    }
    redirect();
}

function handleLicenseStatusUpdate() {
    global $conn;
    $stmt = $conn->prepare("UPDATE license_keys SET is_used = ? WHERE id = ?");
    if ($stmt->execute([$_POST['newStatus'], $_POST['keyId']])) {
        setToastMessage("License key status updated successfully!", "success");
    } else {
        setToastMessage("Error updating license key status.", "error");
    }
    redirect();
}

function handleKeyDeletion() {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM license_keys WHERE id = ?");
    if ($stmt->execute([$_POST['keyId']])) {
        setToastMessage("License key deleted successfully!", "success");
    } else {
        setToastMessage("Error deleting license key.", "error");
    }
    redirect();
}

function handleChangelogOperation($operation) {
    global $conn;
    switch ($operation) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO changelogs (title, description) VALUES (?, ?)");
            $success = $stmt->execute([$_POST['title'], $_POST['description']]);
            $message = $success ? "Changelog added successfully!" : "Error adding changelog.";
            break;
        case 'edit':
            $stmt = $conn->prepare("UPDATE changelogs SET title = ?, description = ? WHERE id = ?");
            $success = $stmt->execute([$_POST['title'], $_POST['description'], $_POST['changelogId']]);
            $message = $success ? "Changelog updated successfully!" : "Error updating changelog.";
            break;
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM changelogs WHERE id = ?");
            $success = $stmt->execute([$_POST['changelogId']]);
            $message = $success ? "Changelog deleted successfully!" : "Error deleting changelog.";
            break;
    }
    setToastMessage($message, $success ? "success" : "error");
    redirect();
}

function getStatusBadge($status) {
    $badges = [
        "Detected"   => "bg-danger",
        "Undetected" => "bg-success",
        "Online"     => "bg-primary",
        "Offline"    => "bg-secondary",
    ];
    return $badges[$status] ?? "bg-dark";
}

function displayLicenseKeys($conn) {
    $stmt = $conn->query("SELECT * FROM license_keys ORDER BY id DESC");
    echo '<div class="table-responsive">
            <table class="table table-dark table-hover text-center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>License Key</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td><code>{$row['key_value']}</code></td>
                <td>
                    <span class='badge " . ($row['is_used'] ? 'bg-danger' : 'bg-success') . "'>
                        " . ($row['is_used'] ? 'Used' : 'Not Used') . "
                    </span>
                </td>
                <td>
                    <button class='btn btn-sm btn-primary' 
                            data-bs-toggle='modal' 
                            data-bs-target='#updateStatusModal'
                            data-keyid='{$row['id']}' 
                            data-isused='{$row['is_used']}'>
                        Update
                    </button>
                    <button class='btn btn-sm btn-danger' 
                            data-bs-toggle='modal' 
                            data-bs-target='#deleteKeyModal'
                            data-keyid='{$row['id']}' 
                            data-keyvalue='{$row['key_value']}'>
                        Delete
                    </button>
                </td>
              </tr>";
    }
    echo '</tbody></table></div>';
}

function displayChangelogs($conn) {
    $stmt = $conn->query("SELECT * FROM changelogs ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li class='changelog-item mb-4 p-4 rounded'>
                <div class='d-flex justify-content-between align-items-start'>
                    <div>
                        <h5 class='mb-2'>{$row['title']}</h5>
                        <p class='mb-2'>{$row['description']}</p>
                        <small class='text-muted'>{$row['created_at']}</small>
                    </div>
                    <div class='btn-group'>
                        <button class='btn btn-sm btn-warning' 
                                data-bs-toggle='modal' 
                                data-bs-target='#editChangelogModal'
                                data-id='{$row['id']}'
                                data-title='{$row['title']}'
                                data-description='{$row['description']}'>
                            ✏️ Edit
                        </button>
                        <button class='btn btn-sm btn-danger' 
                                data-bs-toggle='modal' 
                                data-bs-target='#deleteChangelogModal'
                                data-id='{$row['id']}'
                                data-title='{$row['title']}'>
                            🗑 Delete
                        </button>
                    </div>
                </div>
              </li>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
:root {
    --primary-gradient: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    --card-bg: rgba(255, 255, 255, 0.05);
    --hover-bg: rgba(255, 255, 255, 0.08);
    --border-color: rgba(255, 255, 255, 0.1);
    --text-color: #e5e5e5;
    --transition: all 0.3s ease;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
}

/* Base Styles */
body {
    font-family: 'Inter', sans-serif;
    background: var(--primary-gradient);
    color: var(--text-color);
    min-height: 100vh;
    margin: 0;
    padding: var(--spacing-md);
}

/* Container */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-sm);
}

/* Main Header */
.main-header {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    border: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-md);
}

/* Grid Layout  */
/* .grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
} */

/* Cards */
.dashboard-card {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: var(--spacing-lg);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    background: var(--hover-bg);
}

.card-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
}

/* Tables */
.table-responsive {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    overflow-x: auto;
    margin: 0 -1rem;
    padding: 0 1rem;
    -webkit-overflow-scrolling: touch;
}

.table {
    color: var(--text-color);
    border-color: var(--border-color);
    margin: 0;
    min-width: 600px;
}

.table th,
.table td {
    white-space: nowrap;
    padding: var(--spacing-sm) var(--spacing-md);
}

/* Badges and Buttons */
.badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    white-space: nowrap;
}

.btn {
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    transition: var(--transition);
    white-space: nowrap;
}

.btn:hover {
    transform: translateY(-2px);
}

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.quick-action-btn {
    flex: 1;
    min-width: 150px;
    padding: var(--spacing-md);
    text-align: center;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    transition: var(--transition);
    text-decoration: none;
}

.quick-action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    color: var(--text-color);
}

/* Forms */
.form-control,
.form-select {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    border-radius: 8px;
    transition: var(--transition);
    width: 100%;
}

.form-control:focus,
.form-select:focus {
    background: rgba(0, 0, 0, 0.3);
    border-color: rgba(255, 255, 255, 0.2);
    color: var(--text-color);
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
}

/* Modals */
.modal-content {
    background: #1a1a2e;
    border: 1px solid var(--border-color);
    border-radius: 15px;
    color: var(--text-color);
}

/* Changelog */
.changelog-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.changelog-item {
    background: rgba(0, 0, 0, 0.2);
    transition: var(--transition);
    padding: var(--spacing-lg);
    border-radius: 10px;
    margin-bottom: var(--spacing-md);
}

.changelog-item:hover {
    background: rgba(0, 0, 0, 0.3);
    transform: translateY(-2px);
}

/* Responsive Breakpoints */
@media (max-width: 1200px) {
    .grid-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    :root {
        --spacing-lg: 1.25rem;
        --spacing-xl: 1.5rem;
    }
    
    .dashboard-container {
        padding: 0;
    }
}

@media (max-width: 768px) {
    .grid-container {
        grid-template-columns: 1fr;
    }
    
    .main-header {
        flex-direction: column;
        text-align: center;
        padding: var(--spacing-md);
    }
    
    .quick-actions {
        width: 100%;
        justify-content: center;
    }
    
    .quick-action-btn {
        min-width: 140px;
    }
    
    body {
        padding: var(--spacing-sm);
    }
}

@media (max-width: 576px) {
    .card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .badge {
        padding: 0.4rem 0.8rem;
        font-size: 0.875rem;
    }
    
    .btn {
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
    }
    
    .table th,
    .table td {
        padding: var(--spacing-sm);
        font-size: 0.875rem;
    }
}
    </style>
</head>

<body>
<div class="dashboard-container">
        <!-- Main Header -->
        <div class="main-header">
            
            <div>
                <h2 class="mb-0">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>
                <p class="text-white mb-0">🔑 Total Used Keys: <?= htmlspecialchars($totalUsedKeys) ?></p> 
            </div>
            <div class="quick-actions">
                <button class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#generateKeysModal">
                    🔑 Generate Keys
                </button>
                <button class="quick-action-btn" data-bs-toggle="modal" data-bs-target="#addChangelogModal">
                    📝 Add Changelog
                </button>
                <a href="../logout.php" class="quick-action-btn">
                    🚪 Logout
                </a>
            </div>
        </div>

        <div class="grid-container">
            <!-- Status Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h4 class="mb-0">📡 Software Status</h4>
                </div>
                <div class="card-body">
                    <div class="status-badge <?= getStatusBadge($status) ?>">
                        Current Status: <?= htmlspecialchars($status) ?>
                    </div>
                    <form method="post" class="mt-3">
                        <select class="form-select mb-3" name="newStatus">
                            <option value="Online" <?= $status == 'Online' ? 'selected' : '' ?>>🟢 Online</option>
                            <option value="Offline" <?= $status == 'Offline' ? 'selected' : '' ?>>🔴 Offline</option>
                        </select>
                        <button type="submit" class="btn btn-primary w-100" name="updateSoftwareStatus">
                            Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- License Keys Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h4 class="mb-0">🔑 License Keys Management</h4>
                </div>
                <div class="card-body">
                    <?php displayLicenseKeys($conn); ?>
                </div>
            </div>

            <!-- Changelog Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h4 class="mb-0">📜 Recent Changelogs</h4>
                </div>
                <div class="card-body">
                    <ul class="changelog-list">
                        <?= displayChangelogs($conn) ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Generate License Key Modal -->
    <div class="modal fade" id="generateKeysModal" tabindex="-1" aria-labelledby="generateKeysModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateKeysModalLabel">Generate License Keys</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="generate_keys.php">
                        <div class="mb-3">
                            <label for="numberOfKeys" class="form-label">Number of Keys:</label>
                            <input type="number" class="form-control" id="numberOfKeys" name="numberOfKeys" min="1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="keyFormat" class="form-label">Key Format:</label>
                            <select class="form-select" id="keyFormatSelect" name="keyFormat">
                                <option value="xxxx-xxxx-xxxx">XXXX-XXXX-XXXX</option>
                                <option value="xxxx-xxxx">XXXX-XXXX</option>
                                <option value="xxxx-xxxx-xxxx-xxxx">XXXX-XXXX-XXXX-XXXX</option>
                                <option value="xx-xx-xx">XX-XX-XX</option>
                                <option value="xxx-xxx">XXX-XXX</option>
                                <option value="custom">Custom Format (Enter Below)</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="customKeyFormat"
                                name="customKeyFormat" placeholder="Enter Custom Format">
                        </div>
                        <button type="submit" class="btn btn-success btn-custom">Generate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update License Key Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">Update License Key Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="admin.php">
                        <input type="hidden" name="keyId" id="modalKeyId">
                        <label for="newStatus" class="form-label">Select New Status:</label>
                        <select class="form-select" name="newStatus" id="modalNewStatus">
                            <option value="0">Not Used</option>
                            <option value="1">Used</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-custom mt-3" name="updateLicenseStatus">
                            Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete License Key Modal -->
    <div class="modal fade" id="deleteKeyModal" tabindex="-1" aria-labelledby="deleteKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">Delete License Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this license key?</p>
                    <strong id="deleteKeyValue"></strong>
                    <form method="post" action="./index.php">
                        <input type="hidden" name="keyId" id="deleteKeyId">
                        <button type="submit" class="btn btn-danger btn-custom mt-3" name="deleteKey">🗑 Delete</button>
                        <button type="button" class="btn btn-secondary btn-custom mt-3"
                            data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Changelog Modal -->
    <div class="modal fade" id="addChangelogModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">📝 Add Changelog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="text" class="form-control mb-2" name="title" placeholder="Title" required>
                        <textarea class="form-control" name="description" placeholder="Description" required></textarea>
                        <button type="submit" class="btn btn-success mt-3" name="addChangelog">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Changelog Modal -->
    <div class="modal fade" id="editChangelogModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Edit Changelog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="changelogId" id="editChangelogId">
                        <input type="text" class="form-control mb-2" name="title" id="editChangelogTitle"
                            placeholder="Title" required>
                        <textarea class="form-control" name="description" id="editChangelogDescription"
                            placeholder="Description" required></textarea>
                        <button type="submit" class="btn btn-success mt-3" name="editChangelog">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Changelog Modal -->
    <div class="modal fade" id="deleteChangelogModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">🗑 Delete Changelog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this changelog?</p>
                    <strong id="deleteChangelogTitle"></strong>
                    <form method="post">
                        <input type="hidden" name="changelogId" id="deleteChangelogId">
                        <button type="submit" class="btn btn-danger mt-3" name="deleteChangelog">Delete</button>
                        <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toastify.js -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle Delete Key Modal
        var deleteKeyModal = document.getElementById("deleteKeyModal");
        if (deleteKeyModal) {
            deleteKeyModal.addEventListener("show.bs.modal", function(event) {
                var button = event.relatedTarget;
                var keyId = button.getAttribute("data-keyid");
                var keyValue = button.getAttribute("data-keyvalue");

                document.getElementById("deleteKeyId").value = keyId;
                document.getElementById("deleteKeyValue").textContent = keyValue;
            });
        }

        // Handle Update License Key Status Modal
        var updateStatusModal = document.getElementById("updateStatusModal");
        if (updateStatusModal) {
            updateStatusModal.addEventListener("show.bs.modal", function(event) {
                var button = event.relatedTarget;
                var keyId = button.getAttribute("data-keyid");
                var isUsed = button.getAttribute(
                "data-isused"); // Get the current is_used value (0 or 1)

                document.getElementById("modalKeyId").value = keyId;

                // Ensure the correct option is selected in the dropdown
                var newStatusDropdown = document.getElementById("modalNewStatus");
                if (newStatusDropdown) {
                    newStatusDropdown.value = isUsed;
                }
            });
        }

        // Handle Edit Changelog Modal
        var editChangelogModal = document.getElementById("editChangelogModal");
        if (editChangelogModal) {
            editChangelogModal.addEventListener("show.bs.modal", function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute("data-id");
                var title = button.getAttribute("data-title");
                var description = button.getAttribute("data-description");

                document.getElementById("editChangelogId").value = id;
                document.getElementById("editChangelogTitle").value = title;
                document.getElementById("editChangelogDescription").value = description;
            });
        }

        // Handle Delete Changelog Modal
        var deleteChangelogModal = document.getElementById("deleteChangelogModal");
        if (deleteChangelogModal) {
            deleteChangelogModal.addEventListener("show.bs.modal", function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute("data-id");
                var title = button.getAttribute("data-title");

                document.getElementById("deleteChangelogId").value = id;
                document.getElementById("deleteChangelogTitle").textContent = title;
            });
        }

        // Show Toast Notification
        <?php if (isset($_SESSION['toast_message'])): ?>
        Toastify({
            text: "<?php echo $_SESSION['toast_message']; ?>",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            backgroundColor: "<?php echo ($_SESSION['toast_type'] === 'success') ? '#28a745' : '#dc3545'; ?>",
        }).showToast();
        <?php unset($_SESSION['toast_message'], $_SESSION['toast_type']); ?>
        <?php endif; ?>
    });
    </script>

</body>

</html>