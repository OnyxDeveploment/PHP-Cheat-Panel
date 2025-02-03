<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];

// Fetch user data
$stmt = $conn->prepare("SELECT license_key, created_at FROM users WHERE username = ?");
$stmt->execute([$username]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
$licenseKey = $userData['license_key'] ?? 'N/A';
$createdAt = $userData['created_at'] ?? 'Unknown';

// Fetch latest status set by admin
$statusStmt = $conn->prepare("SELECT value FROM settings WHERE name = 'status'");
$statusStmt->execute();
$status = $statusStmt->fetchColumn() ?? 'Unknown';

// Fetch changelogs from database
$changelogStmt = $conn->prepare("SELECT title, description, created_at FROM changelogs ORDER BY created_at DESC ");
$changelogStmt->execute();
$changelogs = $changelogStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        --card-bg: rgba(255, 255, 255, 0.1);
        --hover-bg: rgba(255, 255, 255, 0.15);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        min-height: 100vh;
        color: #ffffff;
        padding: 2rem;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .welcome-section {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem;
        background: var(--card-bg);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        animation: fadeInDown 0.5s ease;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .dashboard-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        transition: transform 0.3s ease, background-color 0.3s ease;
        animation: fadeIn 0.5s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        background: var(--hover-bg);
    }

    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 1rem;
    }

    .card-header i {
        font-size: 1.5rem;
        margin-right: 0.5rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .status-Detected {
        background: #dc3545;
    }

    .status-Undetected {
        background: #198754;
    }

    .status-Online {
        background: #0dcaf0;
    }

    .status-Offline {
        background: #6c757d;
    }

    .license-key {
        font-family: monospace;
        color: #ffd700;
        padding: 0.5rem;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        word-break: break-all;
    }

    .changelog-item {
        border-left: 3px solid #0d6efd;
        padding-left: 1rem;
        margin-bottom: 1.5rem;
    }

    .btn-custom {
        width: 100%;
        padding: 0.8rem;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        transition: transform 0.3s ease, background-color 0.3s ease;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-custom:hover {
        transform: translateY(-2px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #0061ff 0%, #60efff 100%);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    }

    .btn-discord {
        background: linear-gradient(135deg, #7289da 0%, #5865f2 100%);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-card {
            margin-bottom: 1rem;
        }
    }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="display-5 fw-bold mb-3">
                <i class="bi bi-person-circle"></i>
                Welcome, <?php echo htmlspecialchars($username); ?>!
            </h1>
            <p class="text-muted">Your personal dashboard overview</p>
        </div>

        <div class="stats-grid">
            <!-- User Details Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="bi bi-person-badge"></i>
                    <h4 class="mb-0">User Details</h4>
                </div>
                <div class="card-body">
                    <p><strong>License Key:</strong><br>
                        <span class="license-key"><?php echo htmlspecialchars($licenseKey); ?></span>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-calendar-check"></i>
                        <strong>Account Created:</strong><br>
                        <?php echo htmlspecialchars($createdAt); ?>
                    </p>
                </div>
            </div>

            <!-- Status Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="bi bi-activity"></i>
                    <h4 class="mb-0">System Status</h4>
                </div>
                <div class="card-body text-center">
                    <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                        <i class="bi bi-broadcast"></i>
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="bi bi-lightning-charge"></i>
                    <h4 class="mb-0">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <a href="#" class="btn btn-custom btn-primary">
                        <i class="bi bi-download"></i>
                        Download Program
                    </a>
                    <a href="https://discord.com" target="_blank" class="btn btn-custom btn-discord">
                        <i class="bi bi-discord"></i>
                        Join Discord
                    </a>
                    <a href="logout.php" class="btn btn-custom btn-danger">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Changelog Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <i class="bi bi-journal-text"></i>
                <h4 class="mb-0">Recent Updates</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($changelogs)): ?>
                <?php foreach ($changelogs as $log): ?>
                <div class="changelog-item">
                    <h5><?php echo htmlspecialchars($log['title']); ?></h5>
                    <p class="mb-1"><?php echo htmlspecialchars($log['description']); ?></p>
                    <small class="text-muted">
                        <i class="bi bi-clock"></i>
                        <?php echo $log['created_at']; ?>
                    </small>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted">No updates available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>