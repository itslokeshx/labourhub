<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

// Ensure employer_id is in session
if (!isset($_SESSION['employer_id'])) {
    $emp = mysqli_query($conn, "SELECT id FROM employers WHERE user_id=" . (int)$_SESSION['user_id']);
    $emp_row = mysqli_fetch_assoc($emp);
    if ($emp_row) {
        $_SESSION['employer_id'] = $emp_row['id'];
    } else {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

$employer_id = $_SESSION['employer_id'];

// Handle booking cancellation before fetching stats
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bid = (int)$_GET['cancel'];
    mysqli_query($conn, "UPDATE bookings SET status='rejected' WHERE id=$bid AND employer_id=$employer_id");
    header("Location: employer_dashboard.php");
    exit;
}

// Stats
$total_jobs     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM jobs WHERE employer_id=$employer_id"))['c'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE employer_id=$employer_id"))['c'];
$active_workers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE employer_id=$employer_id AND status='accepted'"))['c'];
$pending_req    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE employer_id=$employer_id AND status='pending'"))['c'];

// Bookings list
$bookings_result = mysqli_query($conn, "
    SELECT b.*, w_user.name AS worker_name, w.skill, j.title AS job_title
    FROM bookings b
    JOIN workers w ON b.worker_id = w.id
    JOIN users w_user ON w.user_id = w_user.id
    JOIN jobs j ON b.job_id = j.id
    WHERE b.employer_id=$employer_id
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ WorkForce</a>
    <div class="nav-links">
        <a href="index.php">Find Workers</a>
        <a href="post_job.php">Post Job</a>
        <a href="employer_dashboard.php" style="color:var(--primary)">Dashboard</a>
        <a href="logout.php" class="btn-nav">Logout</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<div class="dashboard">
    <div class="dashboard-header">
        <div>
            <h1>Employer Dashboard 🏢</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
        </div>
        <div style="display:flex;gap:12px;">
            <a href="index.php" class="btn-outline" style="padding:12px 24px;font-size:0.9rem;">Find Workers</a>
            <a href="post_job.php" class="btn-primary" style="display:inline-block;width:auto;padding:12px 24px;border-radius:50px;font-size:0.9rem;text-decoration:none;">+ Post Job</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <strong><?= $total_jobs ?></strong>
            <span>Jobs Posted</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <strong><?= $total_bookings ?></strong>
            <span>Total Bookings</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👷</div>
            <strong><?= $active_workers ?></strong>
            <span>Active Workers</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <strong><?= $pending_req ?></strong>
            <span>Pending Requests</span>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="dashboard-table">
        <div class="table-title">📅 My Bookings</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Worker</th>
                    <th>Skill</th>
                    <th>Job</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings_result && mysqli_num_rows($bookings_result) > 0):
                    $i = 1;
                    while ($b = mysqli_fetch_assoc($bookings_result)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($b['worker_name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['skill']) ?></td>
                    <td><?= htmlspecialchars($b['job_title']) ?></td>
                    <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                    <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                    <td><span class="status-badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td>
                        <?php if ($b['status'] === 'pending'): ?>
                            <a href="employer_dashboard.php?cancel=<?= $b['id'] ?>" onclick="return confirm('Cancel this booking?')" style="color:var(--danger);font-size:0.85rem;font-weight:600;">Cancel</a>
                        <?php elseif ($b['status'] === 'completed'): ?>
                            <a href="review.php?booking_id=<?= $b['id'] ?>&worker_id=<?= $b['worker_id'] ?>" style="color:var(--primary);font-size:0.85rem;font-weight:600;">Rate Worker</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);font-size:0.85rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">
                        No bookings yet. <a href="index.php" style="color:var(--primary);font-weight:600;">Find workers →</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
