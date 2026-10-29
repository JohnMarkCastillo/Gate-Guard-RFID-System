```php
<?php
require_once "auth.php";
require_once "db.php";

date_default_timezone_set("Asia/Manila");

// Set attendance mode
mysqli_query($conn, "UPDATE system_mode SET mode='attendance' WHERE id=1");

// Philippine date and time
$now = new DateTime("now", new DateTimeZone("Asia/Manila"));
$currentDate = $now->format("Y-m-d");
$hour = (int)$now->format("H");
$countingActive = ($hour >= 5);

$total_students = 0;
$in_today = 0;
$out_today = 0;

// Dashboard counts
if ($countingActive) {
    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(DISTINCT student_id) AS total
        FROM attendance
        WHERE date = ?
        AND time >= '05:00:00'
        AND time <= '23:59:59'
    ");

    mysqli_stmt_bind_param($stmt, "s", $currentDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_students = (int)($row["total"] ?? 0);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT
            SUM(CASE WHEN UPPER(TRIM(status)) = 'IN' THEN 1 ELSE 0 END) AS total_in,
            SUM(CASE WHEN UPPER(TRIM(status)) = 'OUT' THEN 1 ELSE 0 END) AS total_out
        FROM attendance
        WHERE date = ?
        AND time >= '05:00:00'
        AND time <= '23:59:59'
    ");

    mysqli_stmt_bind_param($stmt, "s", $currentDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $in_today = (int)($row["total_in"] ?? 0);
    $out_today = (int)($row["total_out"] ?? 0);

    mysqli_stmt_close($stmt);
}

$adminUsername = $_SESSION["admin_username"] ?? "Admin";
$logoPath = "uploads/school_logo.jpg";
$logoVersion = file_exists(__DIR__ . "/" . $logoPath)
    ? filemtime(__DIR__ . "/" . $logoPath)
    : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>St. Francis Xavier College | Gate Guard Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<style>
:root {
    --green: #087f48;
    --dark-green: #075b36;
    --light-green: #e0f5e9;
    --gold: #f7c928;
    --page-bg: #f1f6f7;
    --text: #34465e;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--page-bg);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 295px;
    padding: 22px 15px;
    background: #fff;
    border-right: 1px solid #e4ebed;
    box-shadow: 3px 0 15px rgba(20, 50, 60, .06);
    overflow-y: auto;
    z-index: 1000;
}

.brand {
    text-align: center;
    padding: 0 5px 24px;
}

.sidebar-logo {
    width: 92px;
    height: 92px;
    object-fit: contain;
    border-radius: 50%;
    border: 3px solid var(--gold);
    background: white;
    padding: 3px;
    margin-bottom: 12px;
}

.brand h2 {
    color: var(--dark-green);
    font-size: 17px;
    font-weight: 800;
    line-height: 1.35;
    margin: 0;
}

.brand p {
    color: #64748b;
    font-size: 11px;
    margin: 5px 0 12px;
}

.brand-line {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
    color: var(--dark-green);
    font-size: 12px;
    font-weight: 800;
}

.brand-line::before,
.brand-line::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #79b99a;
}

.menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 15px;
    min-height: 54px;
    padding: 12px 15px;
    color: #34465e;
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    transition: background .2s, color .2s;
}

.menu a:hover {
    background: #e4f5eb;
    color: var(--dark-green);
}

.menu a.active {
    background: linear-gradient(110deg, #0b8a50, #087342);
    color: white;
    box-shadow: 0 4px 10px rgba(8, 127, 72, .15);
}

.menu-icon {
    width: 25px;
    text-align: center;
    font-size: 21px;
    flex-shrink: 0;
}

.menu-divider {
    border: 0;
    border-top: 1px solid #dce5e8;
    margin: 20px 8px 12px;
}

.menu a.logout:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* MAIN PAGE */
.main {
    margin-left: 295px;
    padding: 18px 25px 0;
    min-height: 100vh;
}

/* TOP HEADER */
.top-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 20px 25px;
    background: white;
    border: 1px solid #edf1f2;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(30, 60, 70, .05);
    margin-bottom: 28px;
}

.top-brand {
    display: flex;
    align-items: center;
    gap: 18px;
    min-width: 0;
}

.top-logo {
    width: 76px;
    height: 76px;
    object-fit: contain;
    border: 3px solid var(--gold);
    border-radius: 50%;
    padding: 2px;
    flex-shrink: 0;
}

.top-brand h1 {
    font-size: 25px;
    font-weight: 800;
    color: var(--dark-green);
    margin: 0 0 5px;
}

.top-brand p {
    color: #64748b;
    margin: 0;
    font-size: 15px;
}

.top-brand .gate-label {
    margin-top: 7px;
    color: var(--dark-green);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 1px;
}

.admin-menu {
    position: relative;
    flex-shrink: 0;
}

.admin-menu summary {
    cursor: pointer;
    list-style: none;
    padding: 10px 13px;
    border-radius: 9px;
    color: var(--dark-green);
    font-weight: 700;
    white-space: nowrap;
}

.admin-menu summary::-webkit-details-marker {
    display: none;
}

.admin-menu summary:hover {
    background: #e5f5eb;
}

.admin-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    min-width: 190px;
    padding: 8px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,.13);
    border: 1px solid #e5e7eb;
    z-index: 1100;
}

.admin-dropdown a {
    display: block;
    text-decoration: none;
    padding: 11px;
    border-radius: 7px;
    color: #34465e;
    font-size: 14px;
}

.admin-dropdown a:hover {
    background: #e5f5eb;
    color: var(--dark-green);
}

.admin-dropdown a.logout-item:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* PAGE TITLE */
.page-title {
    color: var(--dark-green);
    font-size: 35px;
    font-weight: 800;
    margin: 0 0 22px;
    display: flex;
    align-items: center;
    gap: 14px;
}

/* CLOCK HERO */
.hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    align-items: center;
    gap: 28px;
    min-height: 230px;
    padding: 32px 40px;
    margin-bottom: 26px;
    color: white;
    background: linear-gradient(120deg, #08663b, #087f48 65%, #0b8b4d);
    border-radius: 17px;
    box-shadow: 0 7px 20px rgba(8, 90, 50, .15);
}

.hero::before {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    left: -160px;
    bottom: -170px;
    border: 45px solid rgba(255,255,255,.06);
}

.hero::after {
    content: "";
    position: absolute;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    right: -130px;
    top: -130px;
    border: 35px solid rgba(255,255,255,.06);
    pointer-events: none;
}

.clock-section {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.clock-caption {
    font-size: 18px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.live-clock {
    font-size: clamp(32px, 3.5vw, 57px);
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: 1px;
    white-space: nowrap;
}

.live-date {
    font-size: 17px;
    margin-top: 12px;
}

.status-section {
    position: relative;
    z-index: 1;
    border-left: 2px solid rgba(255,255,255,.7);
    padding-left: 35px;
}

.status-panel {
    padding: 22px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.38);
    background: rgba(255,255,255,.13);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.05);
}

.status-title {
    display: flex;
    align-items: center;
    gap: 13px;
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 14px;
}

.status-dot {
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: #43ef91;
    flex-shrink: 0;
    box-shadow: 0 0 12px rgba(67,239,145,.35);
}

.status-panel p {
    margin: 8px 0 0 30px;
    font-size: 15px;
}

/* STAT CARDS */
.stat-card {
    height: 100%;
    min-height: 255px;
    background: white;
    border-radius: 15px;
    border: 1px solid #e8eef0;
    box-shadow: 0 5px 16px rgba(30,60,70,.07);
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 9px 22px rgba(30,60,70,.11);
}

.stat-top {
    height: 5px;
}

.stat-blue .stat-top { background: #2585f5; }
.stat-green .stat-top { background: #0a9b5c; }
.stat-red .stat-top { background: #ff4148; }

.stat-body {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 30px 25px;
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 88px;
    height: 88px;
    border-radius: 50%;
    flex-shrink: 0;
    font-size: 36px;
}

.stat-blue .stat-icon {
    background: #e4efff;
    color: #2585f5;
}

.stat-green .stat-icon {
    background: #dcf7e9;
    color: #079657;
}

.stat-red .stat-icon {
    background: #ffe3e4;
    color: #f23840;
}

.stat-content {
    min-width: 0;
    flex: 1;
}

.stat-content h3 {
    font-size: 18px;
    font-weight: 800;
    color: #34465e;
    margin: 10px 0 7px;
}

.stat-number {
    font-size: 57px;
    font-weight: 800;
    line-height: 1.1;
}

.stat-blue .stat-number { color: #2585f5; }
.stat-green .stat-number { color: #079657; }
.stat-red .stat-number { color: #f23840; }

.stat-description {
    color: #64748b;
    font-size: 14px;
    margin-top: 8px;
    min-height: 20px;
}

.time-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    margin-top: 20px;
    padding: 11px 10px;
    border-radius: 30px;
    font-size: 13px;
    white-space: nowrap;
}

.stat-blue .time-badge {
    background: #edf4ff;
    color: #1672db;
}

.stat-green .time-badge {
    background: #e5f8ee;
    color: #087f48;
}

.stat-red .time-badge {
    background: #fff0f0;
    color: #e52f37;
}

.footer {
    margin: 25px -25px 0;
    padding: 27px 30px 20px;
    text-align: center;
    background: rgba(255,255,255,.35);
    color: #64748b;
    font-size: 12px;
}

.footer-inner {
    display: flex;
    align-items: center;
    gap: 22px;
}

.footer-line {
    height: 1px;
    flex: 1;
    background: #d1dde0;
}

.footer strong {
    display: block;
    color: var(--dark-green);
    font-size: 14px;
    margin-bottom: 5px;
}

.footer p {
    margin: 0 0 7px;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .sidebar {
        width: 240px;
    }

    .main {
        margin-left: 240px;
        padding-left: 18px;
        padding-right: 18px;
    }

    .hero {
        padding: 28px;
        gap: 20px;
    }

    .status-section {
        padding-left: 22px;
    }

    .stat-body {
        padding: 24px 18px;
        gap: 14px;
    }

    .stat-icon {
        width: 65px;
        height: 65px;
        font-size: 28px;
    }

    .stat-number {
        font-size: 48px;
    }

    .footer {
        margin-left: -18px;
        margin-right: -18px;
    }
}

@media (max-width: 900px) {
    .hero {
        grid-template-columns: 1fr;
        gap: 25px;
    }

    .status-section {
        border-left: 0;
        border-top: 1px solid rgba(255,255,255,.5);
        padding: 22px 0 0;
    }

    .live-clock {
        font-size: 42px;
    }

    .top-brand h1 {
        font-size: 19px;
    }

    .top-logo {
        width: 60px;
        height: 60px;
    }
}

@media (max-width: 600px) {
    .sidebar {
        width: 78px;
        padding: 15px 7px;
    }

    .sidebar-logo {
        width: 53px;
        height: 53px;
    }

    .brand h2,
    .brand p,
    .brand-line,
    .menu-text {
        display: none;
    }

    .brand {
        padding-bottom: 20px;
    }

    .menu a {
        justify-content: center;
        padding: 12px 5px;
        min-height: 48px;
    }

    .menu-icon {
        font-size: 23px;
        width: auto;
    }

    .main {
        margin-left: 78px;
        padding: 10px;
    }

    .top-header {
        padding: 13px;
        gap: 8px;
        margin-bottom: 20px;
    }

    .top-brand {
        gap: 9px;
    }

    .top-logo {
        width: 43px;
        height: 43px;
    }

    .top-brand h1 {
        font-size: 13px;
    }

    .top-brand p {
        font-size: 10px;
    }

    .top-brand .gate-label {
        font-size: 10px;
        margin-top: 4px;
    }

    .admin-menu summary {
        font-size: 12px;
        padding: 7px;
    }

    .page-title {
        font-size: 25px;
    }

    .hero {
        padding: 23px 18px;
    }

    .clock-caption {
        font-size: 14px;
    }

    .live-clock {
        font-size: clamp(26px, 7vw, 38px);
        letter-spacing: 0;
    }

    .live-date {
        font-size: 13px;
    }

    .status-panel {
        padding: 16px;
    }

    .status-title {
        font-size: 14px;
    }

    .status-panel p {
        font-size: 12px;
        margin-left: 0;
    }

    .stat-card {
        min-height: auto;
    }

    .stat-body {
        padding: 20px 15px;
    }

    .stat-icon {
        width: 58px;
        height: 58px;
        font-size: 25px;
    }

    .stat-content h3 {
        font-size: 16px;
        margin-top: 5px;
    }

    .stat-number {
        font-size: 42px;
    }

    .stat-description {
        font-size: 12px;
    }

    .time-badge {
        font-size: 11px;
        padding: 9px 6px;
    }

    .footer {
        margin: 20px -10px 0;
        padding: 20px 10px;
    }

    .footer-line {
        display: none;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">
        <img
            src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>?v=<?= $logoVersion ?>"
            alt="School Logo"
            class="sidebar-logo"
        >

        <h2>ST. FRANCIS XAVIER COLLEGE</h2>
        <p>SAN FRANCISCO, AGUSAN DEL SUR</p>

        <div class="brand-line">GATE GUARD</div>
    </div>

    <nav class="menu">

        <a href="index.php" class="active">
            <span class="menu-icon">⌂</span>
            <span class="menu-text">Dashboard</span>
        </a>

        <a href="register.php">
            <span class="menu-icon">＋</span>
            <span class="menu-text">Register Student</span>
        </a>

        <a href="students.php">
            <span class="menu-icon">🎓</span>
            <span class="menu-text">Students</span>
        </a>

        <a href="attendance.php">
            <span class="menu-icon">▤</span>
            <span class="menu-text">Attendance Records</span>
        </a>

    </nav>

    <hr class="menu-divider">

    <nav class="menu">

        <a href="manage_admins.php">
            <span class="menu-icon">♟</span>
            <span class="menu-text">Register Admin</span>
        </a>

        <a href="logout.php" class="logout"
           onclick="return confirm('Are you sure you want to log out?');">
            <span class="menu-icon">⇥</span>
            <span class="menu-text">Logout Admin</span>
        </a>

    </nav>

</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <!-- SCHOOL HEADER -->
    <header class="top-header">

        <div class="top-brand">

            <img
                src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>?v=<?= $logoVersion ?>"
                alt="St. Francis Xavier College Logo"
                class="top-logo"
            >

            <div>
                <h1>ST. FRANCIS XAVIER COLLEGE</h1>
                <p>SAN FRANCISCO, AGUSAN DEL SUR</p>
                <div class="gate-label">────── &nbsp; GATE GUARD &nbsp; ──────</div>
            </div>

        </div>

        <details class="admin-menu">
            <summary>♟ <?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?> ⌄</summary>

            <div class="admin-dropdown">
                <a href="manage_admins.php">♟ Manage Admin Accounts</a>
                <a href="logout.php" class="logout-item"
                   onclick="return confirm('Are you sure you want to log out?');">
                    ⇥ Logout
                </a>
            </div>
        </details>

    </header>

    <!-- PAGE TITLE -->
    <h2 class="page-title">
        <span>⌂</span> Dashboard
    </h2>

    <!-- CLOCK AND COUNTING STATUS -->
    <section class="hero">

        <div class="clock-section">

            <div class="clock-caption">
                <span style="font-size:27px;">◷</span>
                CURRENT PHILIPPINE TIME
            </div>

            <div id="liveClock" class="live-clock">--:--:-- --</div>

            <div id="liveDate" class="live-date">Loading date...</div>

        </div>

        <div class="status-section">

            <div class="status-panel">

                <?php if ($countingActive): ?>

                    <div class="status-title">
                        <span class="status-dot"></span>
                        DAILY COUNTING ACTIVE
                    </div>

                    <p>◷ &nbsp; Counting period: 5:00 AM – 11:59 PM</p>

                    <p>▦ &nbsp; Date: <?= htmlspecialchars(
                        $now->format("F j, Y"),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></p>

                <?php else: ?>

                    <div class="status-title">
                        <span class="status-dot"
                              style="background:#ffd166; box-shadow:none;"></span>
                        DAILY COUNTING RESET
                    </div>

                    <p>◷ &nbsp; Counting is paused until 5:00 AM.</p>

                    <p>▦ &nbsp; Date: <?= htmlspecialchars(
                        $now->format("F j, Y"),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></p>

                <?php endif; ?>

            </div>

        </div>

    </section>

    <!-- STATISTICS -->
    <section class="row g-4">

        <!-- TOTAL STUDENTS -->
        <div class="col-12 col-md-6 col-xl-4">

            <div class="stat-card stat-blue">

                <div class="stat-top"></div>

                <div class="stat-body">

                    <div class="stat-icon">🎓</div>

                    <div class="stat-content">

                        <h3>Total Students</h3>

                        <div class="stat-number">
                            <?= $total_students ?>
                        </div>

                        <div class="stat-description">
                            Students who tapped today
                        </div>

                        <div class="time-badge">
                            ◷ &nbsp; 5:00 AM – 11:59 PM
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- IN TODAY -->
        <div class="col-12 col-md-6 col-xl-4">

            <div class="stat-card stat-green">

                <div class="stat-top"></div>

                <div class="stat-body">

                    <div class="stat-icon">⇥</div>

                    <div class="stat-content">

                        <h3>IN Today</h3>

                        <div class="stat-number">
                            <?= $in_today ?>
                        </div>

                        <div class="stat-description">
                            Total recorded IN transactions
                        </div>

                        <div class="time-badge">
                            ◷ &nbsp; 5:00 AM – 11:59 PM
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- OUT TODAY -->
        <div class="col-12 col-md-6 col-xl-4">

            <div class="stat-card stat-red">

                <div class="stat-top"></div>

                <div class="stat-body">

                    <div class="stat-icon">⇤</div>

                    <div class="stat-content">

                        <h3>OUT Today</h3>

                        <div class="stat-number">
                            <?= $out_today ?>
                        </div>

                        <div class="stat-description">
                            Total recorded OUT transactions
                        </div>

                        <div class="time-badge">
                            ◷ &nbsp; 5:00 AM – 11:59 PM
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="footer">

        <div class="footer-inner">
            <div class="footer-line"></div>

            <div>
                <strong>ST. FRANCIS XAVIER COLLEGE</strong>

                <p>GATE GUARD RFID STUDENT ATTENDANCE MONITORING SYSTEM</p>

                <p>© 2026 All rights reserved.</p>
            </div>

            <div class="footer-line"></div>
        </div>

    </footer>

</main>

<script>
function updateClock() {
    const now = new Date();

    const timeOptions = {
        timeZone: "Asia/Manila",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true
    };

    const dateOptions = {
        timeZone: "Asia/Manila",
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
    };

    document.getElementById("liveClock").textContent =
        new Intl.DateTimeFormat("en-US", timeOptions).format(now);

    document.getElementById("liveDate").textContent =
        new Intl.DateTimeFormat("en-US", dateOptions).format(now);
}

updateClock();
setInterval(updateClock, 1000);

// Refresh dashboard counts periodically.
setInterval(function () {
    window.location.reload();
}, 15000);

// Close admin dropdown when clicking outside it.
document.addEventListener("click", function (event) {
    document.querySelectorAll(".admin-menu[open]").forEach(function (menu) {
        if (!menu.contains(event.target)) {
            menu.removeAttribute("open");
        }
    });
});
</script>

</body>
</html>
```