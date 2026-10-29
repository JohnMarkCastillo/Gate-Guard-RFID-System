<?php
require_once "db.php";

date_default_timezone_set("Asia/Manila");
header_remove("X-Powered-By");

// ==========================================
// CHECK FOR NEW ATTENDANCE
// ==========================================
if (isset($_GET["check"]) && $_GET["check"] === "1") {
    header("Content-Type: application/json; charset=UTF-8");

    $latestID = 0;

    $query = mysqli_query(
        $conn,
        "SELECT id FROM attendance ORDER BY id DESC LIMIT 1"
    );

    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $latestID = (int)$row["id"];
    }

    echo json_encode([
        "success" => true,
        "id" => $latestID
    ]);
    exit();
}

// ==========================================
// GET SPECIFIC ATTENDANCE RECORD
// ==========================================
if (isset($_GET["attendance_id"])) {
    header("Content-Type: application/json; charset=UTF-8");

    $attendanceID = (int)$_GET["attendance_id"];

    if ($attendanceID <= 0) {
        echo json_encode(["success" => false]);
        exit();
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            a.id,
            a.student_id,
            a.fullname,
            a.course,
            a.section,
            a.date,
            a.time,
            a.status,
            s.photo
         FROM attendance a
         LEFT JOIN students s
            ON a.student_id = s.student_id
         WHERE a.id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        echo json_encode(["success" => false]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $attendanceID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($row) {
        echo json_encode([
            "success" => true,
            "attendance" => $row
        ]);
    } else {
        echo json_encode(["success" => false]);
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gate Guard | St. Francis Xavier College</title>

<style>
:root {
    --green: #15803d;
    --dark-green: #14532d;
    --light-green: #f3f7f5;
    --border: #dbe5dd;
    --muted: #64748b;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: var(--light-green);
    color: #1e293b;
    font-family: Arial, Helvetica, sans-serif;
}

/* ==========================================
   PROFESSIONAL SCHOOL HEADER
========================================== */

.school-header {
    background: #ffffff;
    border-bottom: 1px solid var(--border);
    border-top: none;
    padding: 18px 0;
    box-shadow: 0 3px 15px rgba(15, 50, 30, 0.05);
}

.header-inner {
    width: 94%;
    max-width: 1500px;
    margin: auto;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}

.school-logo {
    width: 78px;
    height: 78px;
    object-fit: contain;
    flex-shrink: 0;
}

.school-branding {
    min-width: 0;
}

.school-name {
    margin: 0;
    color: var(--dark-green);
    font-size: clamp(18px, 2.2vw, 28px);
    font-weight: 800;
    letter-spacing: 0.5px;
}

.school-location {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 12px;
    letter-spacing: 1.2px;
}

.system-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 10px;
}

.system-name {
    color: var(--green);
    font-size: 19px;
    font-weight: 800;
    letter-spacing: 2px;
}

.system-subtitle {
    color: #64748b;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1px;
    line-height: 1.5;
}

/* ==========================================
   MAIN CONTENT
========================================== */

.main {
    width: 94%;
    max-width: 1450px;
    margin: 25px auto;
}

/* ==========================================
   MONITOR CARD
========================================== */

.monitor-card {
    min-height: 470px;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 45px;

    padding: 35px;
    overflow: hidden;

    background: #ffffff;
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 18px;

    box-shadow: 0 6px 24px rgba(15, 50, 30, 0.07);
}

/* ==========================================
   STUDENT PHOTO
========================================== */

.photo-section {
    width: 38%;

    display: flex;
    align-items: center;
    justify-content: center;
}

.student-photo,
.no-photo {
    width: clamp(190px, 24vw, 300px);
    height: clamp(190px, 24vw, 300px);

    border-radius: 18px;
    border: 5px solid var(--green);
}

.student-photo {
    object-fit: cover;
    background: #ffffff;
}

.no-photo {
    display: flex;
    align-items: center;
    justify-content: center;

    background: #f1f5f2;
    font-size: 100px;
}

/* ==========================================
   STUDENT INFORMATION
========================================== */

.info-section {
    flex: 1;
    min-width: 0;
}

.welcome {
    margin-bottom: 10px;
    color: var(--green);
    font-size: clamp(20px, 2vw, 27px);
    font-weight: 800;
}

.student-name {
    margin-bottom: 24px;
    color: #17211a;
    font-size: clamp(27px, 3.3vw, 43px);
    font-weight: 800;
    line-height: 1.15;
    overflow-wrap: anywhere;
}

.info-row {
    margin: 13px 0;
    font-size: clamp(16px, 1.5vw, 20px);
    overflow-wrap: anywhere;
}

.info-label {
    color: var(--dark-green);
    font-weight: 800;
}

/* ==========================================
   IN / OUT STATUS
========================================== */

.access-status {
    margin-top: 25px;
    padding: 16px;
    border-radius: 12px;
    text-align: center;

    font-size: clamp(20px, 2.2vw, 29px);
    font-weight: 800;
}

.status-in {
    color: #166534;
    background: #dcfce7;
    border: 2px solid #86c99d;
}

.status-out {
    color: #b42318;
    background: #fee2e2;
    border: 2px solid #f0a1a1;
}

/* ==========================================
   DATE AND TIME
========================================== */

.datetime {
    margin-top: 22px;
    text-align: center;
}

.time {
    color: var(--dark-green);
    font-size: clamp(25px, 3vw, 38px);
    font-weight: 800;
}

.date {
    margin-top: 5px;
    color: var(--muted);
    font-size: clamp(15px, 1.5vw, 20px);
}

/* ==========================================
   WAITING SCREEN
========================================== */

.waiting {
    width: 100%;
    padding: 45px 15px;
    text-align: center;
}

.waiting-icon {
    margin-bottom: 18px;
    color: var(--green);
    font-size: 75px;
}

.waiting h2 {
    margin: 0;
    color: var(--dark-green);
    font-size: clamp(24px, 3vw, 37px);
    font-weight: 800;
}

.waiting p {
    margin-top: 12px;
    color: var(--muted);
    font-size: 17px;
}

/* ==========================================
   FOOTER
========================================== */

.footer {
    padding: 20px 10px 25px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.8;
    text-align: center;
}

.footer strong {
    color: var(--dark-green);
}

/* ==========================================
   MOBILE / TABLET
========================================== */

@media (max-width: 800px) {
    .header-inner {
        justify-content: flex-start;
        gap: 12px;
    }

    .school-logo {
        width: 58px;
        height: 58px;
    }

    .school-name {
        font-size: 17px;
    }

    .school-location {
        font-size: 10px;
        letter-spacing: 0.3px;
    }

    .system-name {
        font-size: 15px;
        letter-spacing: 1.5px;
    }

    .system-subtitle {
        font-size: 8px;
        letter-spacing: 0.4px;
    }

    .monitor-card {
        min-height: auto;
        flex-direction: column;
        gap: 25px;
        padding: 22px 16px;
    }

    .photo-section {
        width: 100%;
    }

    .student-photo,
    .no-photo {
        width: 210px;
        height: 210px;
    }

    .info-section {
        width: 100%;
    }

    .welcome,
    .student-name {
        text-align: center;
    }

    .info-row {
        font-size: 16px;
    }
}
</style>
</head>

<body>

<!-- ==========================================
     SCHOOL HEADER
========================================== -->

<header class="school-header">
    <div class="header-inner">

        <img
            src="uploads/sfxc.webp"
            alt="St. Francis Xavier College Logo"
            class="school-logo"
        >

        <div class="school-branding">

            <h1 class="school-name">
                ST. FRANCIS XAVIER COLLEGE
            </h1>

            <p class="school-location">
                SAN FRANCISCO, AGUSAN DEL SUR
            </p>

            <div class="system-title">
                <span class="system-name">
                    GATE GUARD
                </span>

                <span class="system-subtitle">
                    RFID-BASED STUDENT ATTENDANCE AND GATE MONITORING SYSTEM
                </span>
            </div>

        </div>
    </div>
</header>

<!-- ==========================================
     MONITOR DISPLAY
========================================== -->

<main class="main" id="mainContent">

    <section class="monitor-card">
        <div class="waiting">

            <div class="waiting-icon">📡</div>

            <h2>PLEASE TAP YOUR RFID CARD</h2>

            <p>Waiting for the next student scan...</p>

        </div>
    </section>

</main>

<!-- ==========================================
     FOOTER
========================================== -->

<footer class="footer">
    <strong>ST. FRANCIS XAVIER COLLEGE</strong><br>
    GATE GUARD · RFID STUDENT ATTENDANCE MONITORING SYSTEM<br>
    &copy; 2026 All rights reserved.
</footer>

<script>
let currentAttendanceID = 0;
let loadingAttendance = false;
let displayTimer = null;

/* ==========================================
   ESCAPE DATABASE VALUES
========================================== */

function escapeHTML(value) {
    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* ==========================================
   FORMAT TIME
========================================== */

function formatTime(time) {
    if (!time) return "";

    const parts = String(time).split(":");

    if (parts.length < 2) {
        return escapeHTML(time);
    }

    let hour = parseInt(parts[0], 10);
    const minute = parts[1];
    const second = parts.length >= 3 ? parts[2] : "00";

    const ampm = hour >= 12 ? "PM" : "AM";

    hour = hour % 12;

    if (hour === 0) {
        hour = 12;
    }

    return String(hour).padStart(2, "0") +
        ":" + minute +
        ":" + second +
        " " + ampm;
}

/* ==========================================
   FORMAT DATE
========================================== */

function formatDate(date) {
    if (!date) return "";

    const parts = String(date).split("-");

    if (parts.length !== 3) {
        return escapeHTML(date);
    }

    const year = parts[0];
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);

    const monthNames = [
        "January", "February", "March", "April",
        "May", "June", "July", "August",
        "September", "October", "November", "December"
    ];

    if (month < 1 || month > 12) {
        return escapeHTML(date);
    }

    return monthNames[month - 1] + " " + day + ", " + year;
}

/* ==========================================
   WAITING SCREEN
========================================== */

function showWaitingScreen() {
    document.getElementById("mainContent").innerHTML = `
        <section class="monitor-card">
            <div class="waiting">
                <div class="waiting-icon">📡</div>
                <h2>PLEASE TAP YOUR RFID CARD</h2>
                <p>Waiting for the next student scan...</p>
            </div>
        </section>
    `;
}

/* ==========================================
   CHECK FOR NEW RFID ATTENDANCE
========================================== */

async function checkNewAttendance() {
    try {
        const response = await fetch(
            "gate_monitor.php?check=1&_=" + Date.now(),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!data.success) {
            return;
        }

        const newID = parseInt(data.id, 10);

        if (
            newID > 0 &&
            newID > currentAttendanceID &&
            !loadingAttendance
        ) {
            currentAttendanceID = newID;
            loadingAttendance = true;

            await loadAttendance(newID);
        }

    } catch (error) {
        console.error("Attendance check failed:", error);
    }
}

/* ==========================================
   LOAD STUDENT INFORMATION
========================================== */

async function loadAttendance(attendanceID) {
    try {
        const response = await fetch(
            "gate_monitor.php?attendance_id=" +
            encodeURIComponent(attendanceID) +
            "&_=" + Date.now(),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!data.success || !data.attendance) {
            loadingAttendance = false;
            return;
        }

        const student = data.attendance;

        const isIN = String(student.status || "")
            .trim()
            .toUpperCase() === "IN";

        /* STUDENT PHOTO */

        let photoHTML = `
            <div class="no-photo" aria-label="No student photo">
                👤
            </div>
        `;

        const photoName = String(student.photo || "")
            .split(/[\\/]/)
            .pop();

        if (
            photoName &&
            photoName !== "." &&
            photoName !== ".."
        ) {
            const photoURL = "uploads/" +
                encodeURIComponent(photoName);

            photoHTML = `
                <img
                    src="${escapeHTML(photoURL)}"
                    class="student-photo"
                    alt="Student Photo"
                    onerror="this.outerHTML='<div class=&quot;no-photo&quot;>👤</div>'"
                >
            `;
        }

        const welcomeText = isIN ? "WELCOME!" : "GOODBYE!";

        const statusHTML = isIN
            ? `
                <div class="access-status status-in">
                    ACCESS GRANTED — IN
                </div>
              `
            : `
                <div class="access-status status-out">
                    EXIT RECORDED — OUT
                </div>
              `;

        /* DISPLAY STUDENT */

        document.getElementById("mainContent").innerHTML = `
            <section class="monitor-card">

                <div class="photo-section">
                    ${photoHTML}
                </div>

                <div class="info-section">

                    <div class="welcome">
                        ${welcomeText}
                    </div>

                    <div class="student-name">
                        ${escapeHTML(student.fullname)}
                    </div>

                    <div class="info-row">
                        <span class="info-label">Student ID:</span>
                        ${escapeHTML(student.student_id)}
                    </div>

                    <div class="info-row">
                        <span class="info-label">Course:</span>
                        ${escapeHTML(student.course)}
                    </div>

                    <div class="info-row">
                        <span class="info-label">Section:</span>
                        ${escapeHTML(student.section)}
                    </div>

                    ${statusHTML}

                    <div class="datetime">
                        <div class="time">
                            ${formatTime(student.time)}
                        </div>

                        <div class="date">
                            ${formatDate(student.date)}
                        </div>
                    </div>

                </div>

            </section>
        `;

        /* RETURN TO WAITING SCREEN AFTER 5 SECONDS */

        if (displayTimer) {
            clearTimeout(displayTimer);
        }

        displayTimer = setTimeout(function() {
            showWaitingScreen();
            loadingAttendance = false;
        }, 5000);

    } catch (error) {
        console.error("Unable to load attendance:", error);
        loadingAttendance = false;
    }
}

/* ==========================================
   CHECK EVERY SECOND
========================================== */

setInterval(checkNewAttendance, 1000);

/* START MONITORING */
checkNewAttendance();
</script>

</body>
</html>
