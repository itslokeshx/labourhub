<?php
session_start();
include 'db.php';

// If already logged in as admin, redirect
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (strpos($email, '@') === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid admin email (must contain '@').";
    } else {
        $result = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email'");
        $admin  = mysqli_fetch_assoc($result);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email']= $admin['email'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — labourhub</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<div class="admin-login-page">
    <div class="admin-login-box">
        <div class="admin-shield">🛡️</div>
        <h2>Admin Portal</h2>
        <p class="subtitle">labourhub management console</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" placeholder="admin@labourhub.com" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-admin-login">🔐 Sign In</button>
        </form>

        <div class="admin-login-footer">
            <a href="index.php">← Back to labourhub</a>
        </div>
    </div>
</div>
</body>
</html>
