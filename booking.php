<S?php
session_start();
include 'db.php';

// Only employers can book workers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

// Ensure employer_id is in session (guard against edge cases)
if (!isset($_SESSION['employer_id'])) {
    $emp = mysqli_query($conn, "SELECT id FROM employers WHERE user_id=" . (int)$_SESSION['user_id']);
    $emp_row = mysqli_fetch_assoc($emp);
    if ($emp_row) {
        $_SESSION['employer_id'] = $emp_row['id'];
    } else {
        header("Location: index.php");
        exit;
    }
}

$employer_id = $_SESSION['employer_id'];
$worker_id   = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

if ($worker_id === 0) {
    header("Location: index.php");
    exit;
}

// Get worker details
$worker_result = mysqli_query($conn, "
    SELECT w.*, u.name, u.phone, u.email 
    FROM workers w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.id = $worker_id
");
$worker = mysqli_fetch_assoc($worker_result);

if (!$worker) {
    header("Location: index.php");
    exit;
}

// Get employer's open jobs for selection
$jobs_result = mysqli_query($conn, "SELECT * FROM jobs WHERE employer_id=$employer_id AND status='open'");

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id       = (int)$_POST['job_id'];
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time   = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time     = mysqli_real_escape_string($conn, $_POST['end_time']);
    $message      = mysqli_real_escape_string($conn, trim($_POST['message']));

    if ($job_id <= 0) {
        $error = "Please select a valid job.";
    } elseif (empty($booking_date) || empty($start_time) || empty($end_time)) {
        $error = "Please fill in all required fields.";
    } elseif ($end_time <= $start_time) {
        $error = "End time must be after start time.";
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = "Booking date cannot be in the past.";
    } else {
        // Verify the job belongs to this employer
        $job_check = mysqli_query($conn, "SELECT id FROM jobs WHERE id=$job_id AND employer_id=$employer_id AND status='open'");
        if (mysqli_num_rows($job_check) === 0) {
            $error = "Invalid job selected.";
        } else {
            // Check if worker is already booked on this date
            $check = mysqli_query($conn, "
                SELECT id FROM bookings 
                WHERE worker_id=$worker_id 
                AND booking_date='$booking_date' 
                AND status IN ('pending','accepted')
            ");
            if (mysqli_num_rows($check) > 0) {
                $error = "This worker is already booked on that date. Please choose another date.";
            } else {
                $insert = "INSERT INTO bookings (job_id, worker_id, employer_id, booking_date, start_time, end_time, message, status)
                           VALUES ('$job_id', '$worker_id', '$employer_id', '$booking_date', '$start_time', '$end_time', '$message', 'pending')";
                if (mysqli_query($conn, $insert)) {
                    $success = "✅ Booking request sent successfully! The worker will confirm shortly.";
                } else {
                    $error = "Booking failed. Please try again.";
                }
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
    <title>Book Worker — WorkForce</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="nav-brand">⚡ WorkForce</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="employer_dashboard.php">Dashboard</a>
        <a href="post_job.php">Post Job</a>
        <a href="logout.php" class="btn-nav">Logout</a>
    </div>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
</nav>

<div class="booking-page">
    <div class="booking-container">

        <!-- Breadcrumb -->
        <div style="margin-bottom:24px; color:var(--text-muted); font-size:0.9rem;">
            <a href="index.php" style="color:var(--primary)">Home</a> → 
            <a href="index.php" style="color:var(--primary)">Workers</a> → 
            <strong>Book <?= htmlspecialchars($worker['name']) ?></strong>
        </div>

        <!-- Worker Profile Card -->
        <div class="booking-worker-card">
            <div class="booking-avatar"><?= strtoupper(substr($worker['name'], 0, 1)) ?></div>
            <div class="booking-worker-info">
                <h2><?= htmlspecialchars($worker['name']) ?></h2>
                <p>🔧 <strong><?= htmlspecialchars($worker['skill']) ?></strong></p>
                <p>📍 <?= htmlspecialchars($worker['location']) ?> &nbsp;|&nbsp; 💼 <?= htmlspecialchars($worker['experience'] ?: 'N/A') ?> experience</p>
                <p>📞 <?= htmlspecialchars($worker['phone']) ?> &nbsp;|&nbsp; ✉️ <?= htmlspecialchars($worker['email']) ?></p>
                <p>⭐ Rating: <?= number_format($worker['rating'], 1) ?>/5</p>
                <div class="wage">₹<?= number_format($worker['daily_wage']) ?><small style="font-size:1rem;font-weight:400;color:var(--text-muted)">/day</small></div>
            </div>
            <?php if ($worker['availability'] === 'available'): ?>
                <span style="background:#E8F5E9;color:#388E3C;padding:8px 18px;border-radius:50px;font-weight:700;font-size:0.85rem;align-self:flex-start;">✅ Available</span>
            <?php else: ?>
                <span style="background:#FFEBEE;color:#C62828;padding:8px 18px;border-radius:50px;font-weight:700;font-size:0.85rem;align-self:flex-start;">🔴 Busy</span>
            <?php endif; ?>
        </div>

        <!-- Booking Form -->
        <div class="booking-form-box">
            <h3>📋 Booking Request</h3>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="booking.php?worker_id=<?= $worker_id ?>">

                <!-- Select Job -->
                <div class="form-group">
                    <label>Select Job <span style="color:red">*</span></label>
                    <?php if (mysqli_num_rows($jobs_result) > 0): ?>
                        <select name="job_id" required>
                            <option value="">-- Select a Job --</option>
                            <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                                <option value="<?= $job['id'] ?>" <?= (isset($_POST['job_id']) && $_POST['job_id'] == $job['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($job['title']) ?> — <?= htmlspecialchars($job['location']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    <?php else: ?>
                        <p style="color:var(--primary);font-weight:500;">
                            ⚠️ You have no open jobs. 
                            <a href="post_job.php" style="color:var(--primary);text-decoration:underline">Post a job first →</a>
                        </p>
                        <input type="hidden" name="job_id" value="0">
                    <?php endif; ?>
                </div>

                <!-- Date & Times -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Booking Date <span style="color:red">*</span></label>
                        <input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" value="<?= isset($_POST['booking_date']) ? htmlspecialchars($_POST['booking_date']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <!-- Estimated Cost Display -->
                        <label>Estimated Cost</label>
                        <div id="cost-display" style="padding:14px 18px;background:var(--bg);border-radius:12px;border:2px solid var(--border);font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:var(--primary);">
                            ₹<?= number_format($worker['daily_wage']) ?> / day
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time <span style="color:red">*</span></label>
                        <input type="time" name="start_time" id="start_time" value="<?= isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : '08:00' ?>" required onchange="updateCost()">
                    </div>
                    <div class="form-group">
                        <label>End Time <span style="color:red">*</span></label>
                        <input type="time" name="end_time" id="end_time" value="<?= isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : '17:00' ?>" required onchange="updateCost()">
                    </div>
                </div>

                <!-- Message -->
                <div class="form-group">
                    <label>Message to Worker</label>
                    <textarea name="message" placeholder="Describe the work, location details, tools required..."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                </div>

                <!-- Summary Box -->
                <div style="background:var(--bg);border-radius:12px;padding:20px;margin-bottom:24px;border:1px solid var(--border);">
                    <strong style="font-family:'Syne',sans-serif;display:block;margin-bottom:12px;">📄 Booking Summary</strong>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.9rem;color:var(--text-muted);">
                        <span>Worker:</span> <span style="color:var(--text);font-weight:500;"><?= htmlspecialchars($worker['name']) ?></span>
                        <span>Skill:</span> <span style="color:var(--text);font-weight:500;"><?= htmlspecialchars($worker['skill']) ?></span>
                        <span>Daily Wage:</span> <span style="color:var(--primary);font-weight:700;">₹<?= number_format($worker['daily_wage']) ?></span>
                        <span>Status:</span> <span style="color:var(--success);font-weight:600;">Pending Confirmation</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Send Booking Request 🚀</button>
            </form>
            <?php else: ?>
                <div style="text-align:center;padding:40px 0;">
                    <div style="font-size:4rem;margin-bottom:16px;">🎉</div>
                    <h3 style="margin-bottom:12px;">Request Sent!</h3>
                    <p style="color:var(--text-muted);margin-bottom:28px;">Your booking request has been sent to <?= htmlspecialchars($worker['name']) ?>. You'll be notified when they respond.</p>
                    <a href="employer_dashboard.php" class="btn-primary" style="display:inline-block;width:auto;padding:14px 40px;text-decoration:none;">View Dashboard →</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
const dailyWage = <?= (float)$worker['daily_wage'] ?>;

function updateCost() {
    const start = document.getElementById('start_time').value;
    const end   = document.getElementById('end_time').value;
    if (!start || !end) return;

    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const hours = ((eh * 60 + em) - (sh * 60 + sm)) / 60;

    if (hours <= 0) {
        document.getElementById('cost-display').textContent = 'Invalid time range';
        return;
    }

    const cost = (dailyWage / 8) * hours;
    document.getElementById('cost-display').textContent = 
        '₹' + Math.round(cost).toLocaleString('en-IN') + 
        ' (' + hours.toFixed(1) + ' hrs)';
}

updateCost();
</script>
<script src="script.js"></script>
</body>
</html>
