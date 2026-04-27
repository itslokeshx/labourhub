<?php
session_start();
include '../backend/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'employer' ? 'employer_dashboard.php' : 'worker_dashboard.php'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (strpos($email, '@') === false) {
        $error = "Invalid email format. Must contain '@'.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['worker', 'employer'])) {
        $error = "Invalid role selected.";
    } else {
        // Check duplicate email
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered. Please login.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = "INSERT INTO users (name, email, password, phone, role) VALUES ('$name','$email','$hashed','$phone','$role')";
            if (mysqli_query($conn, $insert)) {
                $user_id = mysqli_insert_id($conn);

                if ($role === 'worker') {
                    mysqli_query($conn, "INSERT INTO workers (user_id, skill, daily_wage, location, experience) VALUES ('$user_id','General','0','Not Set','0 years')");
                } else {
                    mysqli_query($conn, "INSERT INTO employers (user_id, company_name) VALUES ('$user_id','My Company')");
                }

                // Auto-login after registration
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name']    = $name;
                $_SESSION['role']    = $role;
                $_SESSION['email']   = $email;

                if ($role === 'employer') {
                    $emp     = mysqli_query($conn, "SELECT id FROM employers WHERE user_id=$user_id");
                    $emp_row = mysqli_fetch_assoc($emp);
                    $_SESSION['employer_id'] = $emp_row['id'];
                    header("Location: employer_dashboard.php");
                } else {
                    $wrk     = mysqli_query($conn, "SELECT id FROM workers WHERE user_id=$user_id");
                    $wrk_row = mysqli_fetch_assoc($wrk);
                    $_SESSION['worker_id'] = $wrk_row['id'];
                    header("Location: worker_dashboard.php");
                }
                exit;
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
</head>
<body>
<div class="form-page">
    <div class="form-box">
        <h2>⚡ Create Account</h2>
        <p class="form-subtitle">Join WorkForce — find work or hire workers instantly.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>I am a</label>
                <select name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="worker"   <?= (isset($_POST['role']) && $_POST['role']==='worker')   ? 'selected' : '' ?>>👷 Worker</option>
                    <option value="employer" <?= (isset($_POST['role']) && $_POST['role']==='employer') ? 'selected' : '' ?>>🏢 Employer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="+91 9876543210" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
            </div>
            <button type="submit" class="btn-primary">Create Account</button>
        </form>

        <div class="form-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>
</body>
</html>
