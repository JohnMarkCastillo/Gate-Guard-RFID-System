<?php
require_once "auth.php";
require_once "db.php";

$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM attendance
         WHERE fullname LIKE ?
            OR student_id LIKE ?
         ORDER BY id DESC"
    );

    $keyword = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query(
        $conn,
        "SELECT * FROM attendance ORDER BY id DESC"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Attendance Monitoring | Gate Guard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --green: #15803d;
    --dark-green: #14532d;
    --light-green: #dcfce7;
    --background: #f3f7f5;
    --text: #1e293b;
    --muted: #64748b;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}

.topbar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 18px 0;
}

.brand {
    display: flex;
    align-items: center;
    gap: 15px;
}

.school-logo {
    width: 68px;
    height: 68px;
    object-fit: contain;
    flex-shrink: 0;
}

.school-name {
    margin: 0;
    color: var(--dark-green);
    font-size: 20px;
    font-weight: 800;
    line-height: 1.4;
}

.school-location {
    color: var(--muted);
    font-size: 12px;
    letter-spacing: 1px;
    margin: 4px 0;
}

.system-name {
    color: var(--green);
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1.5px;
    margin: 0;
}

.page-container {
    max-width: 1200px;
    margin: 32px auto;
    padding: 0 18px;
}

.page-heading {
    margin-bottom: 22px;
}

.page-heading h1 {
    font-size: 30px;
    color: var(--dark-green);
    font-weight: 800;
    margin: 0 0 8px;
}

.page-heading p {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

.nav-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 22px;
}

.nav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 15px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid transparent;
    transition: .2s;
}

.nav-btn:hover {
    transform: translateY(-1px);
}

.nav-primary {
    color: white;
    background: var(--green);
}

.nav-primary:hover {
    color: white;
    background: var(--dark-green);
}

.nav-secondary {
    color: var(--dark-green);
    background: white;
    border-color: #d5e1d8;
}

.nav-secondary:hover {
    color: var(--dark-green);
    background: var(--light-green);
}

.nav-btn svg {
    width: 18px;
    height: 18px;
}

.main-card {
    background: white;
    border: 1px solid #e1eae4;
    border-radius: 15px;
    box-shadow: 0 6px 24px rgba(15, 50, 30, .05);
    overflow: hidden;
}

.card-heading {
    background: var(--dark-green);
    color: white;
    padding: 21px 24px;
}

.card-heading h2 {
    font-size: 21px;
    font-weight: 700;
    margin: 0;
}

.card-heading p {
    margin: 6px 0 0;
    color: #d1fae5;
    font-size: 13px;
}

.card-body-custom {
    padding: 24px;
}

.search-form {
    display: flex;
    gap: 10px;
    width: 100%;
    max-width: 600px;
    margin-bottom: 24px;
}

.search-input-wrap {
    flex: 1;
    position: relative;
}

.search-input-wrap svg {
    width: 19px;
    height: 19px;
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
}

.search-input {
    width: 100%;
    height: 45px;
    padding: 10px 13px 10px 42px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
}

.search-input:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(21,128,61,.12);
}

.search-btn {
    background: var(--green);
    color: white;
    border: none;
    border-radius: 9px;
    padding: 0 23px;
    font-weight: 600;
}

.search-btn:hover {
    background: var(--dark-green);
}

.table-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 13px;
}

.record-count {
    font-size: 13px;
    color: var(--muted);
}

.table-responsive {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

table {
    margin-bottom: 0 !important;
    vertical-align: middle !important;
}

thead th {
    background: #f0f7f2 !important;
    color: var(--dark-green) !important;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 15px 13px !important;
    border-bottom: 1px solid #d8e5dc !important;
    white-space: nowrap;
}

tbody td {
    padding: 14px 13px !important;
    color: #334155;
    font-size: 13px;
    border-color: #edf1ee !important;
    white-space: nowrap;
}

tbody tr:hover td {
    background: #f7fcf8;
}

.student-name {
    font-weight: 700;
    color: #1e293b;
}

.student-id {
    font-weight: 600;
    color: #475569;
}

.status-badge {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 55px;
    padding: 7px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 800;
}

.status-in {
    background: #dcfce7;
    color: #166534;
}

.status-out {
    background: #fee2e2;
    color: #b91c1c;
}

.empty-state {
    text-align: center;
    padding: 35px 15px !important;
    color: var(--muted);
}

.empty-state svg {
    display: block;
    width: 34px;
    height: 34px;
    margin: 0 auto 10px;
    color: #94a3b8;
}

.footer {
    padding: 25px 10px;
    text-align: center;
    color: #64748b;
    font-size: 12px;
    line-height: 1.8;
}

.footer strong {
    color: var(--dark-green);
}

@media (max-width: 600px) {
    .topbar {
        padding: 14px 0;
    }

    .school-logo {
        width: 52px;
        height: 52px;
    }

    .school-name {
        font-size: 15px;
    }

    .school-location {
        font-size: 10px;
        letter-spacing: .3px;
    }

    .page-container {
        margin-top: 22px;
        padding: 0 12px;
    }

    .page-heading h1 {
        font-size: 25px;
    }

    .card-heading {
        padding: 18px;
    }

    .card-body-custom {
        padding: 15px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-input,
    .search-btn {
        width: 100%;
        height: 44px;
    }

    .nav-btn {
        flex: 1 1 42%;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="container">
        <div class="brand">

            <img
                src="uploads/sfxc.webp"
                alt="St. Francis Xavier College Logo"
                class="school-logo"
            >

            <div>
                <h2 class="school-name">ST. FRANCIS XAVIER COLLEGE</h2>
                <p class="school-location">
                    SAN FRANCISCO, AGUSAN DEL SUR
                </p>
                <p class="system-name">GATE GUARD</p>
            </div>

        </div>
    </div>
</header>

<main class="page-container">

    <div class="page-heading">
        <h1>Attendance Monitoring</h1>
        <p>View and search student RFID attendance records.</p>
    </div>

    <nav class="nav-actions">

        <a href="index.php" class="nav-btn nav-primary">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="m3 10 9-7 9 7"/>
                <path d="M5 9v12h14V9"/>
                <path d="M9 21v-7h6v7"/>
            </svg>
            Dashboard
        </a>

        <a href="register.php" class="nav-btn nav-secondary">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <circle cx="9" cy="7" r="4"/>
                <path d="M3 21v-2a6 6 0 0 1 12 0v2"/>
                <path d="M19 8v6"/>
                <path d="M16 11h6"/>
            </svg>
            Register Student
        </a>

        <a href="students.php" class="nav-btn nav-secondary">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="10" cy="7" r="4"/>
                <path d="M20 8v6"/>
                <path d="M17 11h6"/>
            </svg>
            Students
        </a>

        <a href="attendance.php" class="nav-btn nav-secondary">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M8 2v4"/>
                <path d="M16 2v4"/>
                <rect x="3" y="4" width="18" height="17" rx="2"/>
                <path d="M3 10h18"/>
                <path d="m9 15 2 2 4-4"/>
            </svg>
            Refresh Records
        </a>

    </nav>

    <section class="main-card">

        <div class="card-heading">
            <h2>Student Attendance Records</h2>
            <p>Recorded student IN and OUT transactions</p>
        </div>

        <div class="card-body-custom">

            <form method="GET" action="attendance.php" class="search-form">

                <div class="search-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>

                    <input
                        type="search"
                        name="search"
                        class="search-input"
                        placeholder="Search by student name or ID..."
                        value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                    >
                </div>

                <button type="submit" class="search-btn">
                    Search
                </button>

            </form>

            <div class="table-top">
                <strong style="color:#14532d;font-size:15px">
                    Attendance History
                </strong>

                <span class="record-count">
                    <?php if ($search !== ""): ?>
                        Search results for:
                        <strong><?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?></strong>
                    <?php else: ?>
                        Latest records first
                    <?php endif; ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && mysqli_num_rows($result) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($result)): ?>

                            <tr>
                                <td><?= (int)$row["id"] ?></td>

                                <td class="student-id">
                                    <?= htmlspecialchars($row["student_id"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td class="student-name">
                                    <?= htmlspecialchars($row["fullname"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["course"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["section"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["date"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["time"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?php if (strtoupper($row["status"] ?? "") === "IN"): ?>
                                        <span class="status-badge status-in">IN</span>
                                    <?php else: ?>
                                        <span class="status-badge status-out">OUT</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="empty-state">
                                No attendance records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </section>

    <footer class="footer">
        <strong>ST. FRANCIS XAVIER COLLEGE</strong><br>
        GATE GUARD · RFID STUDENT ATTENDANCE MONITORING SYSTEM<br>
        &copy; 2026 All rights reserved.
    </footer>

</main>

</body>
</html>
