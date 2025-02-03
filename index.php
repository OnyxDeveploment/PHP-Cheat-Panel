<?php
session_start();
require_once 'config.php';

// 🚀 Redirect logged-in users
if (isset($_SESSION['username'])) {
    header("Location: " . ($_SESSION['is_admin'] ? './admin/index.php' : 'dashboard.php'));
    exit;
}

$error = ''; // Initialize error variable to prevent undefined warnings

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (isset($_POST['register'])) {
        $licenseKey = trim($_POST['license_key']);

        $stmt = $conn->prepare("SELECT * FROM license_keys WHERE key_value = ? AND is_used = 0");
        $stmt->execute([$licenseKey]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($key) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, license_key) VALUES (?, ?, ?)");

            if ($stmt->execute([$username, $hashedPassword, $licenseKey])) {
                $conn->prepare("UPDATE license_keys SET is_used = 1 WHERE id = ?")->execute([$key['id']]);
                $error = '<div class="alert alert-success">User registered successfully. Please log in.</div>';
            } else {
                $error = '<div class="alert alert-danger">Error registering user.</div>';
            }
        } else {
            $error = '<div class="alert alert-warning">Invalid or already used license key.</div>';
        }
    } elseif (isset($_POST['login'])) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = (int) $user['is_admin']; // Ensure it is properly set as integer

            header("Location: " . ($_SESSION['is_admin'] ? './admin/index.php' : 'dashboard.php'));
            exit;
        } else {
            $error = '<div class="alert alert-danger">Invalid username or password.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Login or Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            margin: auto;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .auth-header h2 {
            font-weight: 600;
            font-size: 28px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff, #a5a5a5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .auth-header p {
            color: #a5a5a5;
            margin: 0;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            padding: 12px 16px;
            height: auto;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .form-label {
            color: #a5a5a5;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a5a5a5;
        }

        .btn-auth {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            color: white;
        }

        .btn-register {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #5a5ff9, #7c4ef3);
            transform: translateY(-2px);
        }

        .btn-register:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .alert {
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            border: none;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #ef4444;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .alert-warning {
            background: rgba(234, 179, 8, 0.1);
            color: #eab308;
        }

        .toggle-view {
            text-align: center;
            margin-top: 20px;
            color: #a5a5a5;
            font-size: 14px;
        }

        .toggle-view a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }

        .toggle-view a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Please enter your details to continue</p>
            </div>

            <?php echo $error; ?>

            <form action="index.php" method="post" id="authForm">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>

                <div class="form-floating mb-4 license-key-field" style="display: none;">
                    <input type="text" class="form-control" id="license_key" name="license_key" placeholder="License Key">
                    <label for="license_key">License Key</label>
                </div>

                <button type="submit" class="btn btn-auth btn-login mb-3" name="login">Sign In</button>
                <button type="submit" class="btn btn-auth btn-register" name="register" style="display: none;">Create Account</button>

                <div class="toggle-view">
                    <span class="login-text">Don't have an account? <a href="#" onclick="toggleView('register'); return false;">Register</a></span>
                    <span class="register-text" style="display: none;">Already have an account? <a href="#" onclick="toggleView('login'); return false;">Login</a></span>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleView(view) {
            const licenseKeyField = document.querySelector('.license-key-field');
            const loginButton = document.querySelector('.btn-login');
            const registerButton = document.querySelector('.btn-register');
            const loginText = document.querySelector('.login-text');
            const registerText = document.querySelector('.register-text');
            const header = document.querySelector('.auth-header h2');
            const subheader = document.querySelector('.auth-header p');

            if (view === 'register') {
                licenseKeyField.style.display = 'block';
                loginButton.style.display = 'none';
                registerButton.style.display = 'block';
                loginText.style.display = 'none';
                registerText.style.display = 'block';
                header.textContent = 'Create Account';
                subheader.textContent = 'Please fill in your information';
            } else {
                licenseKeyField.style.display = 'none';
                loginButton.style.display = 'block';
                registerButton.style.display = 'none';
                loginText.style.display = 'block';
                registerText.style.display = 'none';
                header.textContent = 'Welcome Back';
                subheader.textContent = 'Please enter your details to continue';
            }
        }
    </script>
</body>
</html>