<?php
require_once "auth.php";
require_once "db.php";

// Delete Student
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM students WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: students.php");
    exit;
}

// Get registered students
$result = mysqli_query(
    $conn,
    "SELECT * FROM students ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registered Students | Gate Guard</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

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

/* SCHOOL HEADER */
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

/* MAIN CONTENT */
.page-container {
    max-width: 1200px;
    margin: 32px auto;
    padding: 0 18px;
}

.page-heading {
    margin-bottom: 22px;
}

.page-heading h1 {
    color: var(--dark-green);
    font-size: 30px;
    font-weight: 800;
    margin: 0 0 8px;
}

.page-heading p {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

/* NAVIGATION */
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
    background: var(--green);
    color: #fff;
}

.nav-primary:hover {
    background: var(--dark-green);
    color: #fff;
}

.nav-secondary {
    background: #fff;
    color: var(--dark-green);
    border-color: #d5e1d8;
}

.nav-secondary:hover {
    background: var(--light-green);
    color: var(--dark-green);
}

.nav-btn svg,
.action-btn svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

/* STUDENT CARD */
.main-card {
    background: #fff;
    border: 1px solid #e1eae4;
    border-radius: 15px;
    box-shadow: 0 6px 24px rgba(15, 50, 30, .05);
    overflow: hidden;
}

.card-heading {
    background: var(--dark-green);
    color: #fff;
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

/* SEARCH */
.search-area {
    position: relative;
    margin-bottom: 24px;
}

.search-area svg {
    width: 19px;
    height: 19px;
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
}

.search-input {
    width: 100%;
    height: 46px;
    padding: 10px 14px 10px 43px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
}

.search-input:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(21, 128, 61, .12);
}

.table-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 13px;
}

.table-title {
    color: var(--dark-green);
    font-size: 15px;
    font-weight: 700;
}

.record-count {
    color: var(--muted);
    font-size: 13px;
}

/* TABLE */
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
    padding: 13px !important;
    color: #334155;
    font-size: 13px;
    border-color: #edf1ee !important;
    white-space: nowrap;
}

tbody tr:hover td {
    background: #f7fcf8;
}

.student-name {
    color: #1e293b;
    font-weight: 700;
}

.student-id {
    color: #475569;
    font-weight: 600;
}

.rfid-badge {
    display: inline-block;
    background: #1e293b;
    color: #fff;
    padding: 7px 10px;
    border-radius: 7px;
    font-family: Consolas, monospace;
    font-size: 12px;
    font-weight: 700;
}

/* ACTION BUTTONS */
.actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: none;
    border-radius: 7px;
    padding: 8px 10px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: .2s;
}

.edit-btn {
    background: #fef3c7;
    color: #92400e;
}

.edit-btn:hover {
    background: #fde68a;
    color: #78350f;
}

.delete-btn {
    background: #fee2e2;
    color: #b91c1c;
}

.delete-btn:hover {
    background: #fecaca;
    color: #991b1b;
}

.empty-state {
    text-align: center;
    padding: 38px 15px !important;
    color: var(--muted);
}

.empty-state svg {
    display: block;
    width: 35px;
    height: 35px;
    margin: 0 auto 10px;
    color: #94a3b8;
}

/* FOOTER */
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

    .nav-btn {
        flex: 1 1 42%;
    }

    .table-top {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
</head>

<body>

<!-- SCHOOL BRANDING -->
<header class="topbar">
    <div class="container">
        <div class="brand">

            <img
                src="uploads/sfxc.webp"
                alt="St. Francis Xavier College Logo"
                class="school-logo"
            >

            <div>
                <h2 class="school-name">
                    ST. FRANCIS XAVIER COLLEGE
                </h2>

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
        <h1>Registered Students</h1>
        <p>Manage student information and registered RFID cards.</p>
    </div>

    <!-- NAVIGATION -->
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
            Register New Student
        </a>

        <a href="attendance.php" class="nav-btn nav-secondary">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <rect x="3" y="4" width="18" height="17" rx="2"/>
                <path d="M8 2v4M16 2v4M3 10h18"/>
                <path d="m9 15 2 2 4-4"/>
            </svg>
            Attendance
        </a>

    </nav>

    <!-- STUDENTS TABLE -->
    <section class="main-card">

        <div class="card-heading">
            <h2>Student Directory</h2>
            <p>Registered student profiles and RFID identification</p>
        </div>

        <div class="card-body-custom">

            <!-- SEARCH -->
            <div class="search-area">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>

                <input
                    type="search"
                    id="search"
                    class="search-input"
                    placeholder="Search by student name, ID, course, section or RFID UID..."
                    autocomplete="off"
                    aria-label="Search registered students"
                >
            </div>

            <div class="table-top">
                <span class="table-title">All Registered Students</span>

                <span class="record-count">
                    Showing <strong id="visibleCount">0</strong>
                    of <strong id="totalCount">0</strong> students
                </span>
            </div>

            <div class="table-responsive">

                <table class="table table-hover" id="studentTable">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>RFID UID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && mysqli_num_rows($result) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($result)): ?>

                            <tr class="student-row">

                                <td>
                                    <?= (int)$row["id"] ?>
                                </td>

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
                                    <span class="rfid-badge">
                                        <?= htmlspecialchars($row["rfid_uid"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="actions">

                                        <a
                                            href="edit_student.php?id=<?= (int)$row["id"] ?>"
                                            class="action-btn edit-btn"
                                            title="Edit student"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 aria-hidden="true">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                            </svg>
                                            Edit
                                        </a>

                                        <a
                                            href="students.php?delete=<?= (int)$row["id"] ?>"
                                            class="action-btn delete-btn"
                                            title="Delete student"
                                            onclick="return confirm('Delete this student? This action cannot be undone.');"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 aria-hidden="true">
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="m19 6-1 14H6L5 6"/>
                                                <path d="M10 11v5M14 11v5"/>
                                            </svg>
                                            Delete
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr id="noStudentsRow">
                            <td colspan="7" class="empty-state">
                                No registered students found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    <tr id="noSearchResults" style="display:none">
                        <td colspan="7" class="empty-state">
                            No matching students found.
                        </td>
                    </tr>

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

<script>
const searchInput = document.getElementById("search");
const studentRows = Array.from(
    document.querySelectorAll(".student-row")
);
const visibleCount = document.getElementById("visibleCount");
const totalCount = document.getElementById("totalCount");
const noSearchResults = document.getElementById("noSearchResults");

totalCount.textContent = studentRows.length;

function searchStudent() {
    const keyword = searchInput.value.trim().toLowerCase();
    let visible = 0;

    studentRows.forEach(function(row) {
        const matches = row.textContent.toLowerCase().includes(keyword);

        row.style.display = matches ? "" : "none";

        if (matches) {
            visible++;
        }
    });

    visibleCount.textContent = visible;
    noSearchResults.style.display =
        visible === 0 && studentRows.length > 0 ? "" : "none";
}

searchInput.addEventListener("input", searchStudent);

searchStudent();
</script>

</body>
</html>
