<?php
session_start();
require_once 'config.php';

// Redirect if not admin
if (!isset($_SESSION['username']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numberOfKeys']) && isset($_POST['keyFormat'])) {
    $numberOfKeys = (int) $_POST['numberOfKeys'];
    $keyFormat = trim($_POST['keyFormat']);

    // Ensure at least 1 key is being created
    if ($numberOfKeys < 1) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Please enter a valid number of keys.</div>";
        header("Location: admin_dashboard.php");
        exit;
    }

    $successCount = 0;
    $failedCount = 0;
    $generatedKeys = [];

    for ($i = 0; $i < $numberOfKeys; $i++) {
        $licenseKey = generateLicenseKey($keyFormat);

        if (!in_array($licenseKey, $generatedKeys) && insertLicenseKey($conn, $licenseKey)) {
            $successCount++;
            $generatedKeys[] = $licenseKey; // Avoid duplicate keys in the loop
        } else {
            $failedCount++;
        }
    }

    // Construct success & error messages
    $_SESSION['message'] = "<div class='alert alert-success'>Generated $successCount keys successfully.</div>";
    if ($failedCount > 0) {
        $_SESSION['message'] .= "<div class='alert alert-danger'>Failed to insert $failedCount keys.</div>";
    }

    header("Location: admin_dashboard.php");
    exit;
}

/**
 * Generates a license key based on a given format.
 * "x" represents a random character (A-Z, a-z, 0-9).
 */
function generateLicenseKey($format)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = '';

    for ($i = 0; $i < strlen($format); $i++) {
        $key .= ($format[$i] === 'x') ? $chars[random_int(0, strlen($chars) - 1)] : $format[$i];
    }

    return strtoupper($key); // Convert to uppercase for better readability
}

/**
 * Inserts a generated key into the database, preventing duplicates.
 */
function insertLicenseKey($conn, $key)
{
    try {
        // Check if the key already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM license_keys WHERE key_value = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            return false; // Avoid inserting duplicate keys
        }

        // Insert the new key
        $stmt = $conn->prepare("INSERT INTO license_keys (key_value, is_used) VALUES (?, 0)");
        return $stmt->execute([$key]);

    } catch (PDOException $e) {
        error_log("Error inserting license key: " . $e->getMessage());
        return false;
    }
}
?>
