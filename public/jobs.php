<?php
session_start();
include '../backend/config/db.php';

$skill_filter    = isset($_GET['skill'])    ? mysqli_real_escape_string($conn, $_GET['skill'])    : '';
$location_filter = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';

$query = "SELECT j.*, u.name AS employer_name FROM jobs j JOIN employers e ON j.employer_id=e.id JOIN users u ON e.user_id=u.id WHERE j.status='open'";
if ($skill_filter)    $query .= " AND j.skill_required LIKE '%$skill_filter%'";
if ($location_filter) $query .= " AND j.location LIKE '%$location_filter%'";
$query .= " ORDER BY j.created_at DESC";

$jobs_result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ WorkForce</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php" style="color:var(--primary)">Browse Jobs</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'employer'): ?>
                <a href="post_job.php">Post Job</a>
                <a href="employer_dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="worker_dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-nav">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn-nav">Register</a>
        <?php endif; ?>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<section style="padding:60px 60px 20px;background:var(--secondary);">
    <h1 style="color:white;font-size:2.5rem;margin-bottom:12px;">Browse Job Openings</h1>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:32px;">Find the right job matching your skills</p>

    <form method="GET" action="jobs.php" style="display:flex;gap:12px;flex-wrap:wrap;background:rgba(255,255,255,0.1);padding:16px;border-radius:var(--radius);">
        <select name="skill" style="flex:1;min-width:180px;padding:12px 16px;border-radius:10px;border:none;font-family:'DM Sans',sans-serif;font-size:0.95rem;">
            <option value="">All Skills</option>
            <?php foreach(['Electrician','Plumber','Construction Worker','Cleaner','Carpenter','Painter','Helper','Driver','Mason','Welder'] as $s): ?>
                <option value="<?= $s ?>" <?= $skill_filter===$s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="location" placeholder="📍 Location" value="<?= htmlspecialchars($location_filter) ?>" style="flex:1;min-width:180px;padding:12px 16px;border-radius:10px;border:none;font-family:'DM Sans',sans-serif;font-size:0.95rem;">
        <button type="submit" class="btn-search">Search Jobs</button>
        <?php if ($skill_filter || $location_filter): ?>
            <a href="jobs.php" style="background:rgba(255,255,255,0.2);color:white;padding:12px 20px;border-radius:10px;font-weight:600;font-size:0.9rem;">✕ Clear</a>
        <?php endif; ?>
    </form>
</section>

<section class="jobs-section" style="background:var(--bg);">
    <h2 class="section-title">
        <?= $jobs_result ? mysqli_num_rows($jobs_result) : 0 ?> Jobs Found
        <?php if ($skill_filter): ?> · <?= htmlspecialchars($skill_filter) ?><?php endif; ?>
        <?php if ($location_filter): ?> in <?= htmlspecialchars($location_filter) ?><?php endif; ?>
    </h2>
    <div class="jobs-grid">
        <?php if ($jobs_result && mysqli_num_rows($jobs_result) > 0):
            while ($job = mysqli_fetch_assoc($jobs_result)): ?>
            <div class="job-card">
                <div class="job-header">
                    <span class="job-skill"><?= htmlspecialchars($job['skill_required']) ?></span>
                    <span class="job-status open">Open</span>
                </div>
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <p class="job-employer">Posted by: <?= htmlspecialchars($job['employer_name']) ?></p>
                <?php if (!empty($job['description'])): ?>
                    <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:14px;"><?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...</p>
                <?php endif; ?>
                <div class="job-meta">
                    <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                    <span>⏱ <?= htmlspecialchars($job['duration'] ?: 'Flexible') ?></span>
                </div>
                <div class="job-footer">
                    <div class="job-wage">₹<?= number_format($job['daily_wage']) ?>/day</div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'worker'): ?>
                        <a href="apply_job.php?job_id=<?= $job['id'] ?>" class="btn-apply">Apply Now</a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn-apply">Login to Apply</a>
                    <?php else: ?>
                        <span style="font-size:0.8rem;color:var(--text-muted);">Employer View</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="no-results">
                <p>😔 No jobs found matching your search. <a href="jobs.php" style="color:var(--primary)">Clear filters →</a></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-brand">⚡ WorkForce</div>
        <p>Smart Labour Management and Hiring Portal</p>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Contact</a>
        </div>
        <p class="footer-copy">© 2025 WorkForce. All rights reserved.</p>
    </div>
</footer>

<script src="../frontend/assets/js/script.js"></script>
</body>
</html>
