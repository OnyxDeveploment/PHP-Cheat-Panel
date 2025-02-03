<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM login_logs ORDER BY login_time DESC");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
    echo "<p><strong>User:</strong> {$log['username']} | <strong>IP:</strong> {$log['ip_address']} | <strong>Device:</strong> {$log['device_type']} | <strong>Login Time:</strong> {$log['login_time']}</p>";
}
?>
