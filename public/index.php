<?php
session_start();
include '../backend/config/db.php';

// Fetch filters
$skill_filter    = isset($_GET['skill'])    ? mysqli_real_escape_string($conn, $_GET['skill'])    : '';
$location_filter = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';

// Workers query
$query = "SELECT w.*, u.name, u.phone, u.profile_photo 
          FROM workers w 
          JOIN users u ON w.user_id = u.id 
          WHERE w.availability = 'available'";

if ($skill_filter)    $query .= " AND w.skill LIKE '%$skill_filter%'";
if ($location_filter) $query .= " AND w.location LIKE '%$location_filter%'";

$workers_result = mysqli_query($conn, $query);

// Jobs query
$jobs_result = mysqli_query($conn, "
    SELECT j.*, u.name AS employer_name 
    FROM jobs j 
    JOIN employers e ON j.employer_id = e.id 
    JOIN users u ON e.user_id = u.id 
    WHERE j.status = 'open' 
    ORDER BY j.created_at DESC 
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>labourhub — Smart Labour Hiring Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ labourhub</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
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
            <a href="admin_login.php" style="font-size:0.85rem;color:var(--text-muted);">🛡️ Admin</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">🔧 Trusted by 100+ Employers</div>
        <h1>Find Skilled Workers <span class="highlight">Instantly</span></h1>
        <p>Connect with verified electricians, plumbers, construction workers & more — fast, safe, and transparent.</p>

        <!-- SEARCH BAR -->
        <form class="search-bar" method="GET" action="index.php">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" name="skill" placeholder="Skill (e.g. Plumber, Electrician)" value="<?= htmlspecialchars($skill_filter) ?>">
            </div>
            <div class="search-input-group">
                <span class="search-icon">📍</span>
                <input type="text" name="location" placeholder="Location (e.g. Chennai)" value="<?= htmlspecialchars($location_filter) ?>">
            </div>
            <button type="submit" class="btn-search">Search Workers</button>
        </form>

        <div class="hero-stats">
            <div class="stat"><strong>10+</strong><span>Workers</span></div>
            <div class="stat"><strong>20+</strong><span>Jobs Posted</span></div>
            <div class="stat"><strong>4.9★</strong><span>Avg Rating</span></div>
        </div>
    </div>
    <div class="hero-image">
        <div class="hero-card floating">
            <div class="hc-icon">👷</div>
            <div>
                <strong>akash g</strong>
                <p>Plumber · ★ 5.0</p>
                <span class="badge-avail">Available Now</span>
            </div>
        </div>
        <div class="hero-card floating2">
            <div class="hc-icon">🔧</div>
            <div>
                <strong>desigan</strong>
                <p>business · ★ 4.9</p>
                <span class="badge-avail">Available Now</span>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="categories">
    <h2 class="section-title">Browse by Skill</h2>
    <div class="cat-grid">
        <?php
        $skills = [
            ['⚡','Electrician'],['🔧','Plumber'],['🏗️','Construction Worker'],
            ['🧹','Cleaner'],['🪚','Carpenter'],['🎨','Painter']
        ];
        foreach($skills as $s):
        ?>
        <a href="index.php?skill=<?= urlencode($s[1]) ?>" class="cat-card">
            <span class="cat-icon"><?= $s[0] ?></span>
            <span><?= $s[1] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- WORKERS LISTING -->
<section class="workers-section">
    <div class="section-header">
        <h2 class="section-title">Available Workers</h2>
        <?php if ($skill_filter || $location_filter): ?>
            <a href="index.php" class="clear-filter">✕ Clear Filter</a>
        <?php endif; ?>
    </div>

    <div class="workers-grid">
        <?php if ($workers_result && mysqli_num_rows($workers_result) > 0):
            while ($w = mysqli_fetch_assoc($workers_result)): ?>
            <div class="worker-card">
                <div class="worker-top">
                    <div class="worker-avatar"><?= strtoupper(substr($w['name'], 0, 1)) ?></div>
                    <div class="worker-info">
                        <h3><?= htmlspecialchars($w['name']) ?></h3>
                        <span class="skill-badge"><?= htmlspecialchars($w['skill']) ?></span>
                    </div>
                    <div class="worker-rating">★ <?= number_format($w['rating'], 1) ?></div>
                </div>
                <div class="worker-details">
                    <p>📍 <?= htmlspecialchars($w['location']) ?></p>
                    <p>💼 <?= htmlspecialchars($w['experience'] ?: 'N/A') ?> exp</p>
                    <p>📞 <?= htmlspecialchars($w['phone']) ?></p>
                </div>
                <div class="worker-footer">
                    <div class="worker-wage">₹<?= number_format($w['daily_wage']) ?><small>/day</small></div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'employer'): ?>
                        <a href="booking.php?worker_id=<?= $w['id'] ?>" class="btn-book">Book Now</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-book">Login to Book</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="no-results">
                <p>😔 No workers found. Try a different search.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- LATEST JOBS -->
<section class="jobs-section">
    <h2 class="section-title">Latest Job Openings</h2>
    <div class="jobs-grid">
        <?php if ($jobs_result && mysqli_num_rows($jobs_result) > 0):
            while ($job = mysqli_fetch_assoc($jobs_result)): ?>
        <div class="job-card">
            <div class="job-header">
                <span class="job-skill"><?= htmlspecialchars($job['skill_required']) ?></span>
                <span class="job-status open">Open</span>
            </div>
            <h3><?= htmlspecialchars($job['title']) ?></h3>
            <p class="job-employer">By: <?= htmlspecialchars($job['employer_name']) ?></p>
            <div class="job-meta">
                <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                <span>⏱ <?= htmlspecialchars($job['duration'] ?: 'Flexible') ?></span>
            </div>
            <div class="job-footer">
                <div class="job-wage">₹<?= number_format($job['daily_wage']) ?>/day</div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'worker'): ?>
                    <a href="apply_job.php?job_id=<?= $job['id'] ?>" class="btn-apply">Apply</a>
                <?php else: ?>
                    <a href="login.php" class="btn-apply">Login to Apply</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; else: ?>
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">
                No open jobs at the moment. <a href="post_job.php" style="color:var(--primary);">Post one →</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="view-all">
        <a href="jobs.php" class="btn-outline">View All Jobs →</a>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works">
    <h2 class="section-title">How It Works</h2>
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-num">01</div>
            <h3>Register</h3>
            <p>Create your account as a worker or employer in minutes.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step-card">
            <div class="step-num">02</div>
            <h3>Search & Match</h3>
            <p>Find workers by skill and location or post your job.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step-card">
            <div class="step-num">03</div>
            <h3>Book & Hire</h3>
            <p>Send a booking request and confirm the hire instantly.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step-card">
            <div class="step-num">04</div>
            <h3>Rate & Review</h3>
            <p>After work, leave a rating to build trust in the community.</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-brand">⚡ labourhub</div>
        <p>Smart Labour Management and Hiring Portal</p>
        <div class="footer-links">
            <a href="#">About</a>
            <a href="#">Privacy</a>
            <a href="#">Contact</a>
            <a href="#">Rating</a>
            <a href="admin_login.php">🛡️ Admin Portal</a>
        </div>
        <p class="footer-copy">© 2026 labourhub. All rights reserved.</p>
    </div>
</footer>

<script src="../frontend/assets/js/script.js"></script>
</body>
</html>
