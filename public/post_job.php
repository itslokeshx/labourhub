<?php
session_start();
include '../backend/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

$employer_id = $_SESSION['employer_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = mysqli_real_escape_string($conn, trim($_POST['title']));
    $skill_required = mysqli_real_escape_string($conn, $_POST['skill_required']);
    $location       = mysqli_real_escape_string($conn, trim($_POST['location']));
    $daily_wage     = (float)$_POST['daily_wage'];
    $duration       = mysqli_real_escape_string($conn, trim($_POST['duration']));
    $description    = mysqli_real_escape_string($conn, trim($_POST['description']));

    if (empty($title) || empty($location) || empty($skill_required)) {
        $error = "Please fill all required fields.";
    } else {
        $insert = "INSERT INTO jobs (employer_id, title, skill_required, location, daily_wage, duration, description) 
                   VALUES ('$employer_id','$title','$skill_required','$location','$daily_wage','$duration','$description')";
        if (mysqli_query($conn, $insert)) {
            $success = "✅ Job posted successfully! Workers can now apply.";
        } else {
            $error = "Failed to post job. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ labourhub</a>
    <div class="nav-links">
        <a href="index.php">Find Workers</a>
        <a href="employer_dashboard.php">Dashboard</a>
        <a href="logout.php" class="btn-nav">Logout</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<div class="form-page" style="background:var(--bg);padding:60px 20px;">
    <div class="form-box" style="max-width:600px;">
        <h2>📋 Post a Job</h2>
        <p class="form-subtitle">Find the right worker for your requirement.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Job Title <span style="color:red">*</span></label>
                <input type="text" name="title" placeholder="e.g. Need Electrician for House Wiring" value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Skill Required <span style="color:red">*</span></label>
                    <select name="skill_required" required>
                        <option value="">-- Select Skill --</option>
                        <?php foreach(['Electrician','Plumber','Construction Worker','Cleaner','Carpenter','Painter','Helper','Driver','Mason','Welder'] as $s): ?>
                            <option value="<?= $s ?>" <?= (isset($_POST['skill_required']) && $_POST['skill_required']===$s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Daily Wage Offered (₹)</label>
                    <input type="number" name="daily_wage" min="0" placeholder="e.g. 800" value="<?= isset($_POST['daily_wage']) ? $_POST['daily_wage'] : '' ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Location <span style="color:red">*</span></label>
                    <input type="text" name="location" placeholder="e.g. Tambaram, Chennai" value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 2 days, 1 week" value="<?= isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" placeholder="Describe the work in detail — scope, location access, tools provided, etc."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <button type="submit" class="btn-primary">Post Job 🚀</button>
        </form>

        <?php if ($success): ?>
        <div style="text-align:center;margin-top:20px;">
            <a href="employer_dashboard.php" style="color:var(--primary);font-weight:600;">View Dashboard →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="../frontend/assets/js/script.js"></script>
</body>
</html>
