<?php
session_start();
include '../backend/config/db.php';

// Only workers can apply
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit;
}

$worker_id = $_SESSION['worker_id'];
$job_id    = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if ($job_id === 0) {
    header("Location: jobs.php");
    exit;
}

// Get job details
$job = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT j.*, u.name AS employer_name, e.id AS employer_id, e.company_name, e.address
    FROM jobs j
    JOIN employers e ON j.employer_id = e.id
    JOIN users u ON e.user_id = u.id
    WHERE j.id = $job_id AND j.status = 'open'
"));

if (!$job) {
    header("Location: jobs.php");
    exit;
}

// Check if already applied
$already = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM bookings WHERE job_id=$job_id AND worker_id=$worker_id"
));

// Get worker profile
$worker = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT w.*, u.name, u.phone, u.email FROM workers w JOIN users u ON w.user_id=u.id WHERE w.id=$worker_id"
));

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already) {
    $avail_date = mysqli_real_escape_string($conn, $_POST['avail_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time   = mysqli_real_escape_string($conn, $_POST['end_time']);
    $message    = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (empty($avail_date) || empty($start_time) || empty($end_time)) {
        $error = "Please fill all required fields.";
    } elseif ($end_time <= $start_time) {
        $error = "End time must be after start time.";
    } elseif (strtotime($avail_date) < strtotime(date('Y-m-d'))) {
        $error = "Date cannot be in the past.";
    } else {
        $insert = "INSERT INTO bookings (job_id, worker_id, employer_id, booking_date, start_time, end_time, message, status)
                   VALUES ('$job_id','$worker_id','{$job['employer_id']}','$avail_date','$start_time','$end_time','$message','pending')";
        if (mysqli_query($conn, $insert)) {
            $success = "true";
            $already = ['id' => mysqli_insert_id($conn)];
        } else {
            $error = "Application failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
    <style>
        .apply-page { min-height: 100vh; background: var(--bg); padding: 40px 60px; }
        .apply-container { max-width: 860px; margin: 0 auto; }

        .job-detail-card {
            background: white; border-radius: var(--radius);
            border: 1px solid var(--border); overflow: hidden; margin-bottom: 28px;
        }
        .job-detail-banner {
            background: linear-gradient(135deg, var(--secondary), #0F3460);
            padding: 36px 40px; color: white;
        }
        .job-detail-banner h1 { font-size: 1.8rem; margin-bottom: 10px; letter-spacing: -0.5px; }
        .job-detail-meta { display: flex; gap: 24px; flex-wrap: wrap; margin-top: 16px; }
        .jd-meta-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: rgba(255,255,255,0.75); }
        .jd-meta-item strong { color: white; }

        .job-detail-body { padding: 32px 40px; }
        .detail-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 28px; }
        .detail-item { background: var(--bg); border-radius: 12px; padding: 18px 20px; }
        .detail-item span { display: block; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .detail-item strong { font-family: 'Syne', sans-serif; font-size: 1.1rem; color: var(--text); }
        .detail-item.highlight strong { color: var(--primary); font-size: 1.3rem; }

        .desc-block { margin-bottom: 28px; }
        .desc-block h4 { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 10px; }
        .desc-block p { color: var(--text); line-height: 1.7; }

        .employer-chip {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.12); border-radius: 50px;
            padding: 8px 18px; font-size: 0.88rem; font-weight: 600; color: white;
        }

        .apply-form-box { background: white; border-radius: var(--radius); padding: 40px; border: 1px solid var(--border); }
        .apply-form-box h3 { font-size: 1.5rem; margin-bottom: 6px; }
        .apply-form-box > p { color: var(--text-muted); margin-bottom: 28px; font-size: 0.9rem; }

        .worker-chip {
            display: flex; align-items: center; gap: 14px;
            background: var(--bg); border-radius: 12px; padding: 16px 20px;
            margin-bottom: 28px; border: 1px solid var(--border); flex-wrap: wrap;
        }
        .wchip-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800; color: white; flex-shrink: 0;
        }
        .wchip-info strong { display: block; font-size: 0.95rem; }
        .wchip-info span { font-size: 0.82rem; color: var(--text-muted); }

        .success-state { text-align: center; padding: 48px 20px; }
        .success-icon { font-size: 5rem; margin-bottom: 20px; }
        .success-state h2 { font-size: 1.8rem; margin-bottom: 10px; }
        .success-state p { color: var(--text-muted); margin-bottom: 32px; max-width: 400px; margin-left: auto; margin-right: auto; }
        .btn-group { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        .already-badge {
            background: #FFF3E0; border: 1px solid #FFE0B2;
            border-radius: 12px; padding: 16px 20px;
            color: #E65100; font-weight: 600; font-size: 0.92rem;
            margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
        }

        @media(max-width:768px){
            .apply-page { padding: 24px; }
            .job-detail-banner { padding: 24px; }
            .job-detail-body { padding: 24px; }
            .detail-grid { grid-template-columns: 1fr 1fr; }
            .apply-form-box { padding: 24px; }
        }
        @media(max-width:480px){ .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ WorkForce</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Browse Jobs</a>
        <a href="worker_dashboard.php">Dashboard</a>
        <a href="logout.php" class="btn-nav">Logout</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<div class="apply-page">
    <div class="apply-container">

        <!-- Breadcrumb -->
        <div style="margin-bottom:24px;color:var(--text-muted);font-size:0.88rem;">
            <a href="index.php" style="color:var(--primary)">Home</a> ›
            <a href="jobs.php" style="color:var(--primary)">Jobs</a> ›
            <strong><?= htmlspecialchars($job['title']) ?></strong>
        </div>

        <!-- Job Detail Card -->
        <div class="job-detail-card">
            <div class="job-detail-banner">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
                    <div>
                        <div style="margin-bottom:10px;">
                            <span style="background:rgba(255,213,79,0.2);color:var(--accent);padding:4px 14px;border-radius:50px;font-size:0.8rem;font-weight:700;"><?= htmlspecialchars($job['skill_required']) ?></span>
                        </div>
                        <h1><?= htmlspecialchars($job['title']) ?></h1>
                        <div class="employer-chip">
                            <span>🏢</span>
                            <?= htmlspecialchars($job['employer_name']) ?><?= $job['company_name'] ? ' · '.htmlspecialchars($job['company_name']) : '' ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-family:'Syne',sans-serif;font-size:2.2rem;font-weight:800;color:var(--accent);">
                            ₹<?= number_format($job['daily_wage']) ?>
                        </div>
                        <div style="font-size:0.85rem;color:rgba(255,255,255,0.6);">per day</div>
                    </div>
                </div>
                <div class="job-detail-meta">
                    <div class="jd-meta-item">📍 <strong><?= htmlspecialchars($job['location']) ?></strong></div>
                    <div class="jd-meta-item">⏱ <strong><?= htmlspecialchars($job['duration'] ?: 'Flexible') ?></strong></div>
                    <div class="jd-meta-item">📅 <strong>Posted <?= date('d M Y', strtotime($job['created_at'])) ?></strong></div>
                    <div class="jd-meta-item">🟢 <strong>Open</strong></div>
                </div>
            </div>

            <div class="job-detail-body">
                <div class="detail-grid">
                    <div class="detail-item highlight">
                        <span>Daily Wage</span>
                        <strong>₹<?= number_format($job['daily_wage']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Skill Required</span>
                        <strong><?= htmlspecialchars($job['skill_required']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Duration</span>
                        <strong><?= htmlspecialchars($job['duration'] ?: 'Flexible') ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Location</span>
                        <strong><?= htmlspecialchars($job['location']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Employer</span>
                        <strong><?= htmlspecialchars($job['employer_name']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Status</span>
                        <strong style="color:var(--success);">✅ Open</strong>
                    </div>
                </div>

                <?php if ($job['description']): ?>
                <div class="desc-block">
                    <h4>Job Description</h4>
                    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($job['address']): ?>
                <div class="desc-block">
                    <h4>Work Address</h4>
                    <p>📍 <?= htmlspecialchars($job['address']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Application Form -->
        <div class="apply-form-box">

            <?php if ($success === 'true'): ?>
                <div class="success-state">
                    <div class="success-icon">🎉</div>
                    <h2>Application Submitted!</h2>
                    <p>Your application has been sent to <strong><?= htmlspecialchars($job['employer_name']) ?></strong>. You'll be notified when they respond.</p>
                    <div class="btn-group">
                        <a href="worker_dashboard.php" class="btn-primary" style="display:inline-block;width:auto;padding:14px 32px;text-decoration:none;">View Dashboard →</a>
                        <a href="jobs.php" class="btn-outline" style="padding:14px 32px;">Browse More Jobs</a>
                    </div>
                </div>

            <?php elseif ($already && $success !== 'true'): ?>
                <h3>Apply for this Job</h3>
                <p>Submit your availability for this position.</p>
                <div class="already-badge">
                    ⚠️ You have already applied for this job. Check your dashboard for status.
                </div>
                <a href="worker_dashboard.php" class="btn-primary" style="display:inline-block;width:auto;padding:14px 32px;text-decoration:none;">View Application Status →</a>

            <?php else: ?>
                <h3>Apply for this Job</h3>
                <p>Submit your availability and a message to the employer.</p>

                <div class="worker-chip">
                    <div class="wchip-avatar"><?= strtoupper(substr($worker['name'], 0, 1)) ?></div>
                    <div class="wchip-info">
                        <strong><?= htmlspecialchars($worker['name']) ?></strong>
                        <span><?= htmlspecialchars($worker['skill']) ?> · ₹<?= number_format($worker['daily_wage']) ?>/day · ⭐ <?= number_format($worker['rating'], 1) ?></span>
                    </div>
                    <span style="margin-left:auto;background:<?= $worker['availability']==='available'?'#E8F5E9':'#FFEBEE' ?>;color:<?= $worker['availability']==='available'?'#388E3C':'#C62828' ?>;padding:4px 14px;border-radius:50px;font-size:0.8rem;font-weight:700;">
                        <?= $worker['availability'] === 'available' ? '✅ Available' : '🔴 Busy' ?>
                    </span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($worker['availability'] !== 'available'): ?>
                    <div class="alert alert-error">⚠️ You are currently marked as busy. Complete your active booking first.</div>
                <?php endif; ?>

                <form method="POST" action="apply_job.php?job_id=<?= $job_id ?>">

                    <div class="form-group">
                        <label>Available Date <span style="color:red">*</span></label>
                        <input type="date" name="avail_date" min="<?= date('Y-m-d') ?>" value="<?= isset($_POST['avail_date']) ? $_POST['avail_date'] : '' ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Time <span style="color:red">*</span></label>
                            <input type="time" name="start_time" id="start_time" value="<?= isset($_POST['start_time']) ? $_POST['start_time'] : '08:00' ?>" required onchange="calcEarning()">
                        </div>
                        <div class="form-group">
                            <label>End Time <span style="color:red">*</span></label>
                            <input type="time" name="end_time" id="end_time" value="<?= isset($_POST['end_time']) ? $_POST['end_time'] : '17:00' ?>" required onchange="calcEarning()">
                        </div>
                    </div>

                    <div style="background:linear-gradient(135deg,#FFF3E0,#FFF8E1);border:1px solid #FFE0B2;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.9rem;color:#E65100;font-weight:600;">💰 Estimated Earnings</span>
                        <span id="earning-display" style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);">₹<?= number_format($job['daily_wage']) ?></span>
                    </div>

                    <div class="form-group">
                        <label>Message to Employer</label>
                        <textarea name="message" placeholder="Introduce yourself — your experience, why you're suitable, any questions..."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                    </div>

                    <div style="background:var(--bg);border-radius:12px;padding:20px;margin-bottom:24px;border:1px solid var(--border);">
                        <strong style="font-family:'Syne',sans-serif;display:block;margin-bottom:14px;font-size:0.95rem;">📄 Application Summary</strong>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:0.88rem;">
                            <div style="color:var(--text-muted);">Job:</div>
                            <div style="font-weight:600;"><?= htmlspecialchars(substr($job['title'],0,40)) ?></div>
                            <div style="color:var(--text-muted);">Your Skill:</div>
                            <div style="font-weight:600;"><?= htmlspecialchars($worker['skill']) ?></div>
                            <div style="color:var(--text-muted);">Your Rate:</div>
                            <div style="font-weight:600;color:var(--primary);">₹<?= number_format($worker['daily_wage']) ?>/day</div>
                            <div style="color:var(--text-muted);">Job Offers:</div>
                            <div style="font-weight:600;color:var(--success);">₹<?= number_format($job['daily_wage']) ?>/day</div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary"
                        <?= $worker['availability'] !== 'available' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                        Submit Application 🚀
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
const jobWage = <?= $job['daily_wage'] ?>;
function calcEarning() {
    const s = document.getElementById('start_time').value;
    const e = document.getElementById('end_time').value;
    if (!s || !e) return;
    const [sh, sm] = s.split(':').map(Number);
    const [eh, em] = e.split(':').map(Number);
    const hours = ((eh * 60 + em) - (sh * 60 + sm)) / 60;
    if (hours <= 0) { document.getElementById('earning-display').textContent = 'Invalid'; return; }
    const earn = (jobWage / 8) * hours;
    document.getElementById('earning-display').textContent = '₹' + Math.round(earn).toLocaleString('en-IN') + ' (' + hours.toFixed(1) + ' hrs)';
}
calcEarning();
</script>
<script src="../frontend/assets/js/script.js"></script>
</body>
</html>
