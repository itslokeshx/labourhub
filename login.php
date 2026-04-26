<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'employer' ? 'employer_dashboard.php' : 'worker_dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (strpos($email, '@') === false) {
        $error = "Invalid email format. Must contain '@'.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];

            if ($user['role'] === 'employer') {
                $emp = mysqli_query($conn, "SELECT id FROM employers WHERE user_id={$user['id']}");
                $emp_row = mysqli_fetch_assoc($emp);
                $_SESSION['employer_id'] = $emp_row['id'];
                header("Location: employer_dashboard.php");
            } else {
                $wrk = mysqli_query($conn, "SELECT id FROM workers WHERE user_id={$user['id']}");
                $wrk_row = mysqli_fetch_assoc($wrk);
                $_SESSION['worker_id'] = $wrk_row['id'];
                header("Location: worker_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-page">
    <div class="form-box">
        <h2>⚡ Welcome Back</h2>
        <p class="form-subtitle">Login to your WorkForce account.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>

        <div class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>
</body>
</html>
