<?php
session_start();
include '../backend/config/db.php';

// Auth guard
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ========================
// HANDLE CRUD OPERATIONS
// ========================
$msg = '';
$msg_type = '';

// --- DELETE Operations ---
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
    $msg = "User deleted successfully."; $msg_type = 'success';
}
if (isset($_GET['delete_job']) && is_numeric($_GET['delete_job'])) {
    $id = (int)$_GET['delete_job'];
    mysqli_query($conn, "DELETE FROM jobs WHERE id=$id");
    $msg = "Job deleted successfully."; $msg_type = 'success';
}
if (isset($_GET['delete_booking']) && is_numeric($_GET['delete_booking'])) {
    $id = (int)$_GET['delete_booking'];
    mysqli_query($conn, "DELETE FROM bookings WHERE id=$id");
    $msg = "Booking deleted successfully."; $msg_type = 'success';
}
if (isset($_GET['delete_review']) && is_numeric($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id=$id");
    $msg = "Review deleted successfully."; $msg_type = 'success';
}

// --- UPDATE Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit User
    if (isset($_POST['edit_user'])) {
        $id    = (int)$_POST['user_id'];
        $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $role  = mysqli_real_escape_string($conn, $_POST['role']);
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email', phone='$phone', role='$role' WHERE id=$id");
        $msg = "User updated successfully."; $msg_type = 'success';
    }

    // Add User
    if (isset($_POST['add_user'])) {
        $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $role     = mysqli_real_escape_string($conn, $_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check for duplicate email
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "Email already exists."; $msg_type = 'error';
        } else {
            mysqli_query($conn, "INSERT INTO users (name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$password', '$role')");
            $new_user_id = mysqli_insert_id($conn);
            // Create corresponding worker/employer row
            if ($role === 'worker') {
                $skill      = mysqli_real_escape_string($conn, trim($_POST['skill'] ?? 'General'));
                $daily_wage = (float)($_POST['daily_wage'] ?? 500);
                $location   = mysqli_real_escape_string($conn, trim($_POST['location'] ?? 'Not Set'));
                mysqli_query($conn, "INSERT INTO workers (user_id, skill, daily_wage, location) VALUES ($new_user_id, '$skill', $daily_wage, '$location')");
            } else {
                $company = mysqli_real_escape_string($conn, trim($_POST['company_name'] ?? 'My Company'));
                $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
                mysqli_query($conn, "INSERT INTO employers (user_id, company_name, address) VALUES ($new_user_id, '$company', '$address')");
            }
            $msg = "User created successfully."; $msg_type = 'success';
        }
    }

    // Edit Worker
    if (isset($_POST['edit_worker'])) {
        $id         = (int)$_POST['worker_id'];
        $skill      = mysqli_real_escape_string($conn, trim($_POST['skill']));
        $experience = mysqli_real_escape_string($conn, trim($_POST['experience']));
        $daily_wage = (float)$_POST['daily_wage'];
        $location   = mysqli_real_escape_string($conn, trim($_POST['location']));
        $avail      = mysqli_real_escape_string($conn, $_POST['availability']);
        $rating     = (float)$_POST['rating'];
        mysqli_query($conn, "UPDATE workers SET skill='$skill', experience='$experience', daily_wage=$daily_wage, location='$location', availability='$avail', rating=$rating WHERE id=$id");
        $msg = "Worker updated successfully."; $msg_type = 'success';
    }

    // Edit Job
    if (isset($_POST['edit_job'])) {
        $id             = (int)$_POST['job_id'];
        $title          = mysqli_real_escape_string($conn, trim($_POST['title']));
        $skill_required = mysqli_real_escape_string($conn, trim($_POST['skill_required']));
        $location       = mysqli_real_escape_string($conn, trim($_POST['location']));
        $daily_wage     = (float)$_POST['daily_wage'];
        $duration       = mysqli_real_escape_string($conn, trim($_POST['duration']));
        $description    = mysqli_real_escape_string($conn, trim($_POST['description']));
        $status         = mysqli_real_escape_string($conn, $_POST['status']);
        mysqli_query($conn, "UPDATE jobs SET title='$title', skill_required='$skill_required', location='$location', daily_wage=$daily_wage, duration='$duration', description='$description', status='$status' WHERE id=$id");
        $msg = "Job updated successfully."; $msg_type = 'success';
    }

    // Edit Booking
    if (isset($_POST['edit_booking'])) {
        $id     = (int)$_POST['booking_id'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $bdate  = mysqli_real_escape_string($conn, $_POST['booking_date']);
        $stime  = mysqli_real_escape_string($conn, $_POST['start_time']);
        $etime  = mysqli_real_escape_string($conn, $_POST['end_time']);
        mysqli_query($conn, "UPDATE bookings SET status='$status', booking_date='$bdate', start_time='$stime', end_time='$etime' WHERE id=$id");
        $msg = "Booking updated successfully."; $msg_type = 'success';
    }
}

// ========================
// FETCH ALL DATA
// ========================

// Stats
$total_users     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_workers   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM workers"))['c'];
$total_employers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM employers"))['c'];
$total_jobs      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM jobs"))['c'];
$total_bookings  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings"))['c'];

// Users
$users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");

// Workers
$workers_result = mysqli_query($conn, "
    SELECT w.*, u.name, u.email, u.phone 
    FROM workers w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.id DESC
");

// Employers
$employers_result = mysqli_query($conn, "
    SELECT e.*, u.name, u.email, u.phone 
    FROM employers e 
    JOIN users u ON e.user_id = u.id 
    ORDER BY e.id DESC
");

// Jobs
$jobs_result = mysqli_query($conn, "
    SELECT j.*, u.name AS employer_name 
    FROM jobs j 
    JOIN employers e ON j.employer_id = e.id 
    JOIN users u ON e.user_id = u.id 
    ORDER BY j.created_at DESC
");

// Bookings
$bookings_result = mysqli_query($conn, "
    SELECT b.*, 
           w_user.name AS worker_name, w.skill,
           e_user.name AS employer_name, j.title AS job_title
    FROM bookings b
    JOIN workers w ON b.worker_id = w.id
    JOIN users w_user ON w.user_id = w_user.id
    JOIN employers e ON b.employer_id = e.id
    JOIN users e_user ON e.user_id = e_user.id
    JOIN jobs j ON b.job_id = j.id
    ORDER BY b.created_at DESC
");

// Reviews
$reviews_result = mysqli_query($conn, "
    SELECT r.*, 
           rev_user.name AS reviewer_name,
           w_user.name AS worker_name
    FROM reviews r
    JOIN users rev_user ON r.reviewer_id = rev_user.id
    JOIN workers w ON r.worker_id = w.id
    JOIN users w_user ON w.user_id = w_user.id
    ORDER BY r.created_at DESC
");

// Current tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — labourhub</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/admin_style.css">
</head>
<body>
<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <h2>⚡ labourhub</h2>
            <small>Admin Console</small>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Main</div>
                <a href="admin_dashboard.php?tab=overview" class="sidebar-link <?= $active_tab === 'overview' ? 'active' : '' ?>">
                    <span class="link-icon">📊</span> Overview
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Management</div>
                <a href="admin_dashboard.php?tab=users" class="sidebar-link <?= $active_tab === 'users' ? 'active' : '' ?>">
                    <span class="link-icon">👥</span> Users
                    <span class="link-count"><?= $total_users ?></span>
                </a>
                <a href="admin_dashboard.php?tab=workers" class="sidebar-link <?= $active_tab === 'workers' ? 'active' : '' ?>">
                    <span class="link-icon">👷</span> Workers
                    <span class="link-count"><?= $total_workers ?></span>
                </a>
                <a href="admin_dashboard.php?tab=employers" class="sidebar-link <?= $active_tab === 'employers' ? 'active' : '' ?>">
                    <span class="link-icon">🏢</span> Employers
                    <span class="link-count"><?= $total_employers ?></span>
                </a>
                <a href="admin_dashboard.php?tab=jobs" class="sidebar-link <?= $active_tab === 'jobs' ? 'active' : '' ?>">
                    <span class="link-icon">💼</span> Jobs
                    <span class="link-count"><?= $total_jobs ?></span>
                </a>
                <a href="admin_dashboard.php?tab=bookings" class="sidebar-link <?= $active_tab === 'bookings' ? 'active' : '' ?>">
                    <span class="link-icon">📅</span> Bookings
                    <span class="link-count"><?= $total_bookings ?></span>
                </a>
                <a href="admin_dashboard.php?tab=reviews" class="sidebar-link <?= $active_tab === 'reviews' ? 'active' : '' ?>">
                    <span class="link-icon">⭐</span> Reviews
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Quick Links</div>
                <a href="index.php" class="sidebar-link" target="_blank">
                    <span class="link-icon">🌐</span> View Site
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?= strtoupper(substr($_SESSION['admin_name'], 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">☰</button>
                <div>
                    <h1><?php
                        $titles = [
                            'overview' => '📊 Dashboard Overview',
                            'users' => '👥 Manage Users',
                            'workers' => '👷 Manage Workers',
                            'employers' => '🏢 Manage Employers',
                            'jobs' => '💼 Manage Jobs',
                            'bookings' => '📅 Manage Bookings',
                            'reviews' => '⭐ Manage Reviews'
                        ];
                        echo $titles[$active_tab] ?? 'Dashboard';
                    ?></h1>
                    <span class="topbar-breadcrumb">
                        <a href="admin_dashboard.php">Admin</a> / <?= ucfirst($active_tab) ?>
                    </span>
                </div>
            </div>
            <div class="topbar-right">
                <a href="admin_logout.php" class="btn-topbar danger">🚪 Logout</a>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- OVERVIEW TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'overview'): ?>
            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-box users">👥</div>
                    </div>
                    <span class="stat-value"><?= $total_users ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-box workers">👷</div>
                    </div>
                    <span class="stat-value"><?= $total_workers ?></span>
                    <span class="stat-label">Workers</span>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-box employers">🏢</div>
                    </div>
                    <span class="stat-value"><?= $total_employers ?></span>
                    <span class="stat-label">Employers</span>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-box jobs">💼</div>
                    </div>
                    <span class="stat-value"><?= $total_jobs ?></span>
                    <span class="stat-label">Jobs Posted</span>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-box bookings">📅</div>
                    </div>
                    <span class="stat-value"><?= $total_bookings ?></span>
                    <span class="stat-label">Bookings</span>
                </div>
            </div>

            <!-- Recent Users Table -->
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>👥 Recent Users</h3>
                    <a href="admin_dashboard.php?tab=users" class="btn-topbar outline">View All →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                        if ($recent_users && mysqli_num_rows($recent_users) > 0):
                            $i = 1;
                            while ($u = mysqli_fetch_assoc($recent_users)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                                    <div class="user-cell-info">
                                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                                        <span><?= htmlspecialchars($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="empty-state">No users yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Jobs Table -->
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>💼 Recent Jobs</h3>
                    <a href="admin_dashboard.php?tab=jobs" class="btn-topbar outline">View All →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Employer</th>
                            <th>Skill</th>
                            <th>Wage</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_jobs = mysqli_query($conn, "SELECT j.*, u.name AS emp_name FROM jobs j JOIN employers e ON j.employer_id=e.id JOIN users u ON e.user_id=u.id ORDER BY j.created_at DESC LIMIT 5");
                        if ($recent_jobs && mysqli_num_rows($recent_jobs) > 0):
                            $i = 1;
                            while ($j = mysqli_fetch_assoc($recent_jobs)): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                            <td><?= htmlspecialchars($j['emp_name']) ?></td>
                            <td><span class="badge badge-worker"><?= htmlspecialchars($j['skill_required']) ?></span></td>
                            <td style="font-weight:700;color:var(--primary);">₹<?= number_format($j['daily_wage']) ?></td>
                            <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" class="empty-state">No jobs yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- USERS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'users'): ?>
            <div style="display:flex;gap:12px;margin-bottom:24px;">
                <button class="btn-topbar primary" onclick="openModal('addUserModal')">➕ Add User</button>
            </div>

            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>👥 All Users (<?= $total_users ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        mysqli_data_seek($users_result, 0);
                        if ($users_result && mysqli_num_rows($users_result) > 0):
                            while ($u = mysqli_fetch_assoc($users_result)): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                                    <div class="user-cell-info">
                                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                                        <span><?= htmlspecialchars($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-action edit" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit">✏️</button>
                                    <a href="admin_dashboard.php?tab=users&delete_user=<?= $u['id'] ?>" class="btn-action delete" onclick="return confirm('Delete user <?= htmlspecialchars(addslashes($u['name'])) ?>? This will also delete their worker/employer profile and all related data.')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👥</div><p>No users found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add User Modal -->
            <div class="modal-overlay" id="addUserModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>➕ Add New User</h3>
                        <button class="modal-close" onclick="closeModal('addUserModal')">✕</button>
                    </div>
                    <form method="POST" action="admin_dashboard.php?tab=users">
                        <input type="hidden" name="add_user" value="1">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" required placeholder="John Doe">
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" required placeholder="john@example.com">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" placeholder="9876543210">
                                </div>
                                <div class="form-group">
                                    <label>Role *</label>
                                    <select name="role" id="addUserRole" onchange="toggleRoleFields()">
                                        <option value="worker">Worker</option>
                                        <option value="employer">Employer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" required placeholder="Minimum 6 characters">
                            </div>
                            <!-- Worker-specific fields -->
                            <div id="workerFields">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Skill</label>
                                        <select name="skill">
                                            <option>Electrician</option><option>Plumber</option>
                                            <option>Construction Worker</option><option>Cleaner</option>
                                            <option>Carpenter</option><option>Painter</option>
                                            <option>Helper</option><option>Driver</option>
                                            <option>Mason</option><option>Welder</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Daily Wage (₹)</label>
                                        <input type="number" name="daily_wage" value="500" min="100">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" name="location" placeholder="e.g. Chennai">
                                </div>
                            </div>
                            <!-- Employer-specific fields -->
                            <div id="employerFields" style="display:none;">
                                <div class="form-group">
                                    <label>Company Name</label>
                                    <input type="text" name="company_name" placeholder="e.g. ABC Construction">
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" placeholder="Company address"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal cancel" onclick="closeModal('addUserModal')">Cancel</button>
                            <button type="submit" class="btn-modal save">Create User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal-overlay" id="editUserModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>✏️ Edit User</h3>
                        <button class="modal-close" onclick="closeModal('editUserModal')">✕</button>
                    </div>
                    <form method="POST" action="admin_dashboard.php?tab=users">
                        <input type="hidden" name="edit_user" value="1">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" id="editUserName" required>
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" id="editUserEmail" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" id="editUserPhone">
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role" id="editUserRole">
                                        <option value="worker">Worker</option>
                                        <option value="employer">Employer</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal cancel" onclick="closeModal('editUserModal')">Cancel</button>
                            <button type="submit" class="btn-modal save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- WORKERS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'workers'): ?>
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>👷 All Workers (<?= $total_workers ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Worker</th>
                            <th>Skill</th>
                            <th>Experience</th>
                            <th>Wage/Day</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($workers_result && mysqli_num_rows($workers_result) > 0):
                            while ($w = mysqli_fetch_assoc($workers_result)): ?>
                        <tr>
                            <td><?= $w['id'] ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?= strtoupper(substr($w['name'], 0, 1)) ?></div>
                                    <div class="user-cell-info">
                                        <strong><?= htmlspecialchars($w['name']) ?></strong>
                                        <span><?= htmlspecialchars($w['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-worker"><?= htmlspecialchars($w['skill']) ?></span></td>
                            <td><?= htmlspecialchars($w['experience'] ?: '—') ?></td>
                            <td style="font-weight:700;color:var(--primary);">₹<?= number_format($w['daily_wage']) ?></td>
                            <td><?= htmlspecialchars($w['location']) ?></td>
                            <td><span class="badge badge-<?= $w['availability'] ?>"><?= ucfirst($w['availability']) ?></span></td>
                            <td>⭐ <?= number_format($w['rating'], 1) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-action edit" onclick="editWorker(<?= htmlspecialchars(json_encode($w)) ?>)" title="Edit">✏️</button>
                                    <a href="admin_dashboard.php?tab=users&delete_user=<?= $w['user_id'] ?>" class="btn-action delete" onclick="return confirm('Delete this worker? This will remove all their data.')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="9"><div class="empty-state"><div class="empty-icon">👷</div><p>No workers found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Worker Modal -->
            <div class="modal-overlay" id="editWorkerModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>✏️ Edit Worker</h3>
                        <button class="modal-close" onclick="closeModal('editWorkerModal')">✕</button>
                    </div>
                    <form method="POST" action="admin_dashboard.php?tab=workers">
                        <input type="hidden" name="edit_worker" value="1">
                        <input type="hidden" name="worker_id" id="editWorkerId">
                        <div class="modal-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Skill *</label>
                                    <select name="skill" id="editWorkerSkill">
                                        <option>Electrician</option><option>Plumber</option>
                                        <option>Construction Worker</option><option>Cleaner</option>
                                        <option>Carpenter</option><option>Painter</option>
                                        <option>Helper</option><option>Driver</option>
                                        <option>Mason</option><option>Welder</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Experience</label>
                                    <input type="text" name="experience" id="editWorkerExp" placeholder="e.g. 3 years">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Daily Wage (₹) *</label>
                                    <input type="number" name="daily_wage" id="editWorkerWage" min="100" required>
                                </div>
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input type="text" name="location" id="editWorkerLocation" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Availability</label>
                                    <select name="availability" id="editWorkerAvail">
                                        <option value="available">Available</option>
                                        <option value="busy">Busy</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Rating</label>
                                    <input type="number" name="rating" id="editWorkerRating" min="0" max="5" step="0.1">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal cancel" onclick="closeModal('editWorkerModal')">Cancel</button>
                            <button type="submit" class="btn-modal save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- EMPLOYERS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'employers'): ?>
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>🏢 All Employers (<?= $total_employers ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employer</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($employers_result && mysqli_num_rows($employers_result) > 0):
                            while ($e = mysqli_fetch_assoc($employers_result)): ?>
                        <tr>
                            <td><?= $e['id'] ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?= strtoupper(substr($e['name'], 0, 1)) ?></div>
                                    <div class="user-cell-info">
                                        <strong><?= htmlspecialchars($e['name']) ?></strong>
                                        <span><?= htmlspecialchars($e['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($e['company_name'] ?: '—') ?></strong></td>
                            <td><?= htmlspecialchars($e['phone'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($e['address'] ?: '—') ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="admin_dashboard.php?tab=users&delete_user=<?= $e['user_id'] ?>" class="btn-action delete" onclick="return confirm('Delete this employer? This will remove all their data including jobs.')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🏢</div><p>No employers found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- JOBS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'jobs'): ?>
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>💼 All Jobs (<?= $total_jobs ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Employer</th>
                            <th>Skill</th>
                            <th>Location</th>
                            <th>Wage/Day</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($jobs_result && mysqli_num_rows($jobs_result) > 0):
                            while ($j = mysqli_fetch_assoc($jobs_result)): ?>
                        <tr>
                            <td><?= $j['id'] ?></td>
                            <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                            <td><?= htmlspecialchars($j['employer_name']) ?></td>
                            <td><span class="badge badge-worker"><?= htmlspecialchars($j['skill_required']) ?></span></td>
                            <td><?= htmlspecialchars($j['location']) ?></td>
                            <td style="font-weight:700;color:var(--primary);">₹<?= number_format($j['daily_wage']) ?></td>
                            <td><?= htmlspecialchars($j['duration'] ?: '—') ?></td>
                            <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                            <td><?= date('d M', strtotime($j['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-action edit" onclick="editJob(<?= htmlspecialchars(json_encode($j)) ?>)" title="Edit">✏️</button>
                                    <a href="admin_dashboard.php?tab=jobs&delete_job=<?= $j['id'] ?>" class="btn-action delete" onclick="return confirm('Delete this job?')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">💼</div><p>No jobs found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Job Modal -->
            <div class="modal-overlay" id="editJobModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>✏️ Edit Job</h3>
                        <button class="modal-close" onclick="closeModal('editJobModal')">✕</button>
                    </div>
                    <form method="POST" action="admin_dashboard.php?tab=jobs">
                        <input type="hidden" name="edit_job" value="1">
                        <input type="hidden" name="job_id" id="editJobId">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Job Title *</label>
                                <input type="text" name="title" id="editJobTitle" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Skill Required *</label>
                                    <input type="text" name="skill_required" id="editJobSkill" required>
                                </div>
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input type="text" name="location" id="editJobLocation" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Daily Wage (₹)</label>
                                    <input type="number" name="daily_wage" id="editJobWage" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Duration</label>
                                    <input type="text" name="duration" id="editJobDuration" placeholder="e.g. 5 days">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="editJobDesc"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="editJobStatus">
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal cancel" onclick="closeModal('editJobModal')">Cancel</button>
                            <button type="submit" class="btn-modal save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- BOOKINGS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'bookings'): ?>
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>📅 All Bookings (<?= $total_bookings ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Worker</th>
                            <th>Employer</th>
                            <th>Job</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($bookings_result && mysqli_num_rows($bookings_result) > 0):
                            while ($b = mysqli_fetch_assoc($bookings_result)): ?>
                        <tr>
                            <td><?= $b['id'] ?></td>
                            <td><strong><?= htmlspecialchars($b['worker_name']) ?></strong></td>
                            <td><?= htmlspecialchars($b['employer_name']) ?></td>
                            <td><?= htmlspecialchars($b['job_title']) ?></td>
                            <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                            <td><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-action edit" onclick="editBooking(<?= htmlspecialchars(json_encode($b)) ?>)" title="Edit">✏️</button>
                                    <a href="admin_dashboard.php?tab=bookings&delete_booking=<?= $b['id'] ?>" class="btn-action delete" onclick="return confirm('Delete this booking?')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📅</div><p>No bookings found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Booking Modal -->
            <div class="modal-overlay" id="editBookingModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>✏️ Edit Booking</h3>
                        <button class="modal-close" onclick="closeModal('editBookingModal')">✕</button>
                    </div>
                    <form method="POST" action="admin_dashboard.php?tab=bookings">
                        <input type="hidden" name="edit_booking" value="1">
                        <input type="hidden" name="booking_id" id="editBookingId">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="editBookingStatus">
                                    <option value="pending">Pending</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Booking Date</label>
                                <input type="date" name="booking_date" id="editBookingDate">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" name="start_time" id="editBookingStart">
                                </div>
                                <div class="form-group">
                                    <label>End Time</label>
                                    <input type="time" name="end_time" id="editBookingEnd">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-modal cancel" onclick="closeModal('editBookingModal')">Cancel</button>
                            <button type="submit" class="btn-modal save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======================== -->
            <!-- REVIEWS TAB -->
            <!-- ======================== -->
            <?php if ($active_tab === 'reviews'): ?>
            <div class="admin-table-wrap">
                <div class="table-header">
                    <h3>⭐ All Reviews</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reviewer</th>
                            <th>Worker</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($reviews_result && mysqli_num_rows($reviews_result) > 0):
                            while ($r = mysqli_fetch_assoc($reviews_result)): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><strong><?= htmlspecialchars($r['reviewer_name']) ?></strong></td>
                            <td><?= htmlspecialchars($r['worker_name']) ?></td>
                            <td>
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span style="color:<?= $s <= $r['rating'] ? '#FFD54F' : '#DDD' ?>;">★</span>
                                <?php endfor; ?>
                            </td>
                            <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['comment'] ?: '—') ?></td>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="admin_dashboard.php?tab=reviews&delete_review=<?= $r['id'] ?>" class="btn-action delete" onclick="return confirm('Delete this review?')" title="Delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">⭐</div><p>No reviews found.</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
// ===== Modal Controls =====
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ===== Edit Functions =====
function editUser(user) {
    document.getElementById('editUserId').value    = user.id;
    document.getElementById('editUserName').value  = user.name;
    document.getElementById('editUserEmail').value = user.email;
    document.getElementById('editUserPhone').value = user.phone || '';
    document.getElementById('editUserRole').value  = user.role;
    openModal('editUserModal');
}

function editWorker(w) {
    document.getElementById('editWorkerId').value      = w.id;
    document.getElementById('editWorkerSkill').value    = w.skill;
    document.getElementById('editWorkerExp').value      = w.experience || '';
    document.getElementById('editWorkerWage').value     = w.daily_wage;
    document.getElementById('editWorkerLocation').value = w.location;
    document.getElementById('editWorkerAvail').value    = w.availability;
    document.getElementById('editWorkerRating').value   = w.rating;
    openModal('editWorkerModal');
}

function editJob(j) {
    document.getElementById('editJobId').value       = j.id;
    document.getElementById('editJobTitle').value    = j.title;
    document.getElementById('editJobSkill').value    = j.skill_required;
    document.getElementById('editJobLocation').value = j.location;
    document.getElementById('editJobWage').value     = j.daily_wage;
    document.getElementById('editJobDuration').value = j.duration || '';
    document.getElementById('editJobDesc').value     = j.description || '';
    document.getElementById('editJobStatus').value   = j.status;
    openModal('editJobModal');
}

function editBooking(b) {
    document.getElementById('editBookingId').value     = b.id;
    document.getElementById('editBookingStatus').value = b.status;
    document.getElementById('editBookingDate').value   = b.booking_date;
    document.getElementById('editBookingStart').value  = b.start_time;
    document.getElementById('editBookingEnd').value    = b.end_time;
    openModal('editBookingModal');
}

// ===== Toggle Role Fields on Add User =====
function toggleRoleFields() {
    var role = document.getElementById('addUserRole').value;
    document.getElementById('workerFields').style.display   = role === 'worker' ? 'block' : 'none';
    document.getElementById('employerFields').style.display = role === 'employer' ? 'block' : 'none';
}

// ===== Sidebar Mobile Toggle =====
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
}

// ===== Auto-hide alerts =====
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() { alert.style.display = 'none'; }, 500);
        }, 4000);
    });
});
</script>
</body>
</html>
