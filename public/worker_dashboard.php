<?php
session_start();
include '../backend/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit;
}

// Ensure worker_id is in session
if (!isset($_SESSION['worker_id'])) {
    $wrk = mysqli_query($conn, "SELECT id FROM workers WHERE user_id=" . (int)$_SESSION['user_id']);
    $wrk_row = mysqli_fetch_assoc($wrk);
    if ($wrk_row) {
        $_SESSION['worker_id'] = $wrk_row['id'];
    } else {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

$worker_id = $_SESSION['worker_id'];
$user_id   = $_SESSION['user_id'];

// Update booking status
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bid    = (int)$_GET['id'];
    $action = $_GET['action'];
    if (in_array($action, ['accepted', 'rejected', 'completed'])) {
        mysqli_query($conn, "UPDATE bookings SET status='$action' WHERE id=$bid AND worker_id=$worker_id");
        if ($action === 'accepted') {
            mysqli_query($conn, "UPDATE workers SET availability='busy' WHERE id=$worker_id");
        } elseif ($action === 'completed' || $action === 'rejected') {
            mysqli_query($conn, "UPDATE workers SET availability='available' WHERE id=$worker_id");
        }
    }
    header("Location: worker_dashboard.php");
    exit;
}

// Get worker profile
$worker = mysqli_fetch_assoc(mysqli_query($conn, "SELECT w.*, u.name, u.phone, u.email FROM workers w JOIN users u ON w.user_id=u.id WHERE w.id=$worker_id"));

// Stats
$total_jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE worker_id=$worker_id"))['c'];
$completed  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE worker_id=$worker_id AND status='completed'"))['c'];
$pending    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE worker_id=$worker_id AND status='pending'"))['c'];

// Bookings
$bookings_result = mysqli_query($conn, "
    SELECT b.*, e_user.name AS employer_name, j.title AS job_title, j.location AS job_location
    FROM bookings b
    JOIN employers e ON b.employer_id = e.id
    JOIN users e_user ON e.user_id = e_user.id
    JOIN jobs j ON b.job_id = j.id
    WHERE b.worker_id=$worker_id
    ORDER BY b.created_at DESC
");

// Profile update
$profile_error   = '';
$profile_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $skill      = mysqli_real_escape_string($conn, trim($_POST['skill']));
    $experience = mysqli_real_escape_string($conn, trim($_POST['experience']));
    $daily_wage = (float)$_POST['daily_wage'];
    $location   = mysqli_real_escape_string($conn, trim($_POST['location']));

    if (empty($skill) || empty($location) || $daily_wage <= 0) {
        $profile_error = "Please fill all required fields with valid values.";
    } else {
        mysqli_query($conn, "UPDATE workers SET skill='$skill', experience='$experience', daily_wage='$daily_wage', location='$location' WHERE id=$worker_id");
        $profile_success = "Profile updated successfully!";
        // Refresh worker data
        $worker = mysqli_fetch_assoc(mysqli_query($conn, "SELECT w.*, u.name, u.phone, u.email FROM workers w JOIN users u ON w.user_id=u.id WHERE w.id=$worker_id"));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
    <style>
        .profile-section { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
        .profile-card { background: white; border-radius: var(--radius); padding: 32px; border: 1px solid var(--border); }
        @media(max-width:768px){ .profile-section { grid-template-columns: 1fr; } }
        /* Fix: td action buttons — use inline-flex instead of flex on td */
        .action-cell { display: flex; gap: 8px; align-items: center; }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ WorkForce</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <a href="worker_dashboard.php" style="color:var(--primary)">Dashboard</a>
        <a href="logout.php" class="btn-nav">Logout</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<div class="dashboard">
    <div class="dashboard-header">
        <div>
            <h1>Worker Dashboard 👷</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($worker['name']) ?></strong></p>
        </div>
        <div>
            <span style="background:<?= $worker['availability']==='available' ? '#E8F5E9' : '#FFEBEE' ?>;color:<?= $worker['availability']==='available' ? '#388E3C' : '#C62828' ?>;padding:10px 20px;border-radius:50px;font-weight:700;font-size:0.9rem;">
                <?= $worker['availability'] === 'available' ? '✅ Available' : '🔴 Busy' ?>
            </span>
        </div>
    </div>

    <!-- Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <strong><?= $total_jobs ?></strong>
            <span>Total Bookings</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <strong><?= $completed ?></strong>
            <span>Completed Jobs</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <strong><?= $pending ?></strong>
            <span>Pending Requests</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <strong><?= number_format($worker['rating'], 1) ?></strong>
            <span>Average Rating</span>
        </div>
    </div>

    <!-- Profile & Update -->
    <div class="profile-section">
        <!-- Profile Info -->
        <div class="profile-card">
            <h3 style="margin-bottom:20px;">My Profile</h3>
            <div style="display:flex;flex-direction:column;gap:12px;font-size:0.95rem;">
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);">Name</span>
                    <strong><?= htmlspecialchars($worker['name']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);">Skill</span>
                    <strong><?= htmlspecialchars($worker['skill']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);">Experience</span>
                    <strong><?= htmlspecialchars($worker['experience'] ?: 'N/A') ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);">Daily Wage</span>
                    <strong style="color:var(--primary);">₹<?= number_format($worker['daily_wage']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-muted);">Location</span>
                    <strong><?= htmlspecialchars($worker['location']) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;">
                    <span style="color:var(--text-muted);">Phone</span>
                    <strong><?= htmlspecialchars($worker['phone']) ?></strong>
                </div>
            </div>
        </div>

        <!-- Update Profile -->
        <div class="profile-card">
            <h3 style="margin-bottom:20px;">Update Profile</h3>
            <?php if ($profile_success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($profile_success) ?></div>
            <?php endif; ?>
            <?php if ($profile_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($profile_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Skill</label>
                    <select name="skill">
                        <?php foreach(['Electrician','Plumber','Construction Worker','Cleaner','Carpenter','Painter','Helper','Driver','Mason','Welder'] as $s): ?>
                            <option value="<?= $s ?>" <?= $worker['skill']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Experience</label>
                    <input type="text" name="experience" value="<?= htmlspecialchars($worker['experience'] ?: '') ?>" placeholder="e.g. 3 years">
                </div>
                <div class="form-group">
                    <label>Daily Wage (₹) <span style="color:red">*</span></label>
                    <input type="number" name="daily_wage" value="<?= (float)$worker['daily_wage'] ?>" min="100" required>
                </div>
                <div class="form-group">
                    <label>Location <span style="color:red">*</span></label>
                    <input type="text" name="location" value="<?= htmlspecialchars($worker['location']) ?>" placeholder="e.g. Chennai" required>
                </div>
                <button type="submit" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="dashboard-table">
        <div class="table-title">📅 Booking Requests</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employer</th>
                    <th>Job</th>
                    <th>Location</th>
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
                    <td><strong><?= htmlspecialchars($b['employer_name']) ?></strong></td>
                    <td><?= htmlspecialchars($b['job_title']) ?></td>
                    <td><?= htmlspecialchars($b['job_location']) ?></td>
                    <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                    <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                    <td><span class="status-badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td>
                        <div class="action-cell">
                        <?php if ($b['status'] === 'pending'): ?>
                            <a href="worker_dashboard.php?action=accepted&id=<?= $b['id'] ?>" style="color:var(--success);font-weight:600;font-size:0.85rem;">Accept</a>
                            <a href="worker_dashboard.php?action=rejected&id=<?= $b['id'] ?>" onclick="return confirm('Reject this booking?')" style="color:var(--danger);font-weight:600;font-size:0.85rem;">Reject</a>
                        <?php elseif ($b['status'] === 'accepted'): ?>
                            <a href="worker_dashboard.php?action=completed&id=<?= $b['id'] ?>" onclick="return confirm('Mark as completed?')" style="color:#1565C0;font-weight:600;font-size:0.85rem;">Mark Done</a>
                        <?php else: ?>
                            <span style="color:var(--text-muted);font-size:0.85rem;">—</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">
                        No booking requests yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../frontend/assets/js/script.js"></script>
</body>
</html>
