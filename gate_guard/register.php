
<?php
require_once "auth.php";
require_once "db.php";

date_default_timezone_set("Asia/Manila");

$message = "";

// Set system mode to registration
mysqli_query(
    $conn,
    "UPDATE system_mode SET mode='register' WHERE id=1"
);

// Clear previous RFID scans when registration page opens
mysqli_query($conn, "DELETE FROM rfid_scans");

// Save student
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save"])) {

    $student_id = trim($_POST["student_id"] ?? "");
    $fullname = trim($_POST["fullname"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $section = trim($_POST["section"] ?? "");
    $parent_phone = trim($_POST["parent_phone"] ?? "");
    $rfid_uid = strtoupper(trim($_POST["rfid_uid"] ?? ""));

    if (
        $student_id === "" ||
        $fullname === "" ||
        $course === "" ||
        $section === "" ||
        $parent_phone === ""
    ) {
        $message = "<div class='alert alert-danger'>Please complete all required fields.</div>";

    } elseif ($rfid_uid === "") {
        $message = "<div class='alert alert-danger'>Please tap an RFID card first.</div>";

    } else {

        // Check whether RFID UID is already registered
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM students
             WHERE UPPER(TRIM(rfid_uid)) = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $rfid_uid);
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);
        $duplicate = mysqli_num_rows($check) > 0;
        mysqli_stmt_close($stmt);

        if ($duplicate) {
            $message = "<div class='alert alert-danger'>RFID UID <strong>" .
                htmlspecialchars($rfid_uid, ENT_QUOTES, "UTF-8") .
                "</strong> is already registered.</div>";

        } else {

            $photo_name = "";
            $photo_error = "";

            // Optional student photo
            if (
                isset($_FILES["photo"]) &&
                $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
                    $photo_error = "Photo upload failed.";

                } else {
                    $file_tmp = $_FILES["photo"]["tmp_name"];
                    $file_size = $_FILES["photo"]["size"];
                    $extension = strtolower(
                        pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION)
                    );

                    $allowed = ["jpg", "jpeg", "png", "webp"];

                    if (!in_array($extension, $allowed, true)) {
                        $photo_error = "Invalid photo format. Use JPG, JPEG, PNG, or WEBP.";

                    } elseif ($file_size > 5000000) {
                        $photo_error = "Photo is too large. Maximum size is 5MB.";

                    } elseif (!is_uploaded_file($file_tmp) ||
                              getimagesize($file_tmp) === false) {
                        $photo_error = "Please upload a valid image.";

                    } else {
                        if (!is_dir("uploads")) {
                            mkdir("uploads", 0755, true);
                        }

                        $photo_name = time() . "_" .
                            bin2hex(random_bytes(8)) . "." . $extension;

                        if (!move_uploaded_file(
                            $file_tmp,
                            "uploads/" . $photo_name
                        )) {
                            $photo_error = "Failed to upload photo.";
                            $photo_name = "";
                        }
                    }
                }
            }

            if ($photo_error !== "") {
                $message = "<div class='alert alert-danger'>" .
                    htmlspecialchars($photo_error, ENT_QUOTES, "UTF-8") .
                    "</div>";

            } else {

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO students
                    (student_id, fullname, course, section,
                     rfid_uid, parent_phone, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssss",
                    $student_id,
                    $fullname,
                    $course,
                    $section,
                    $rfid_uid,
                    $parent_phone,
                    $photo_name
                );

                if (mysqli_stmt_execute($stmt)) {

                    // Remove the RFID scan used for registration
                    $scan = mysqli_prepare(
                        $conn,
                        "DELETE FROM rfid_scans WHERE UPPER(TRIM(uid)) = ?"
                    );

                    mysqli_stmt_bind_param($scan, "s", $rfid_uid);
                    mysqli_stmt_execute($scan);
                    mysqli_stmt_close($scan);

                    $message = "
                    <div class='alert alert-success'>
                        Student registered successfully!
                        <br><strong>Student ID:</strong> " .
                        htmlspecialchars($student_id, ENT_QUOTES, "UTF-8") .
                        "<br><strong>RFID:</strong> " .
                        htmlspecialchars($rfid_uid, ENT_QUOTES, "UTF-8") .
                        "<br><strong>Parent Phone:</strong> " .
                        htmlspecialchars($parent_phone, ENT_QUOTES, "UTF-8") .
                    "</div>";

                } else {
                    $message = "<div class='alert alert-danger'>Unable to save student. Please check the database.</div>";

                    // Remove uploaded photo if the database insert failed
                    if ($photo_name !== "" && file_exists("uploads/" . $photo_name)) {
                        unlink("uploads/" . $photo_name);
                    }
                }

                mysqli_stmt_close($stmt);
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

<title>Student Registration | Gate Guard</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>
:root {
    --green: #15803d;
    --dark-green: #14532d;
    --background: #f3f7f5;
    --border: #dbe5dd;
    --muted: #64748b;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: #1e293b;
    font-family: Arial, Helvetica, sans-serif;
}

.school-header {
    background: #fff;
    border-bottom: 1px solid var(--border);
    padding: 18px 0;
}

.school-brand {
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
}

.school-location {
    margin: 5px 0;
    color: var(--muted);
    font-size: 12px;
    letter-spacing: 1px;
}

.system-label {
    margin: 0;
    color: var(--green);
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1.5px;
}

.page-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 16px;
}

.page-title {
    margin-bottom: 20px;
    color: var(--dark-green);
    font-size: 29px;
    font-weight: 800;
}

.registration-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 6px 24px rgba(15, 50, 30, .06);
}

.card-heading {
    padding: 20px;
    background: var(--dark-green);
    color: white;
    text-align: center;
}

.card-heading h2 {
    margin: 0;
    font-size: 22px;
    font-weight: bold;
}

.card-body {
    padding: 25px;
}

.rfid-box {
    padding: 25px;
    border: 2px dashed #86b99a;
    border-radius: 12px;
    background: #f7fcf8;
}

.rfid-box h4 {
    color: var(--dark-green);
    font-size: 21px;
    font-weight: bold;
}

.rfid-box p {
    color: var(--muted);
}

#rfid_uid {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: white;
    color: var(--dark-green);
    font-size: 24px;
    font-weight: bold;
    text-align: center;
    letter-spacing: 2px;
}

#rfid_uid.rfid-detected {
    border: 2px solid var(--green);
    background: #dcfce7;
}

#rfid_status {
    min-height: 28px;
    margin-top: 14px;
    font-size: 15px;
    font-weight: bold;
}

.form-label {
    color: #334155;
    font-weight: 700;
}

.form-control {
    padding: 12px;
    border-color: #cbd5e1;
    border-radius: 8px;
}

.form-control:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgb(21 128 61 / 12%);
}

.btn {
    padding: 11px 15px;
    border-radius: 8px;
    font-weight: 600;
}

.btn-success {
    background: var(--green);
    border-color: var(--green);
}

.btn-success:hover {
    background: var(--dark-green);
    border-color: var(--dark-green);
}

.btn-outline-secondary {
    background: white;
}

.footer {
    padding: 24px 10px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.8;
    text-align: center;
}

.footer strong {
    color: var(--dark-green);
}

@media (max-width: 600px) {
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

    .page-title {
        font-size: 24px;
    }

    .card-body {
        padding: 15px;
    }

    .rfid-box {
        padding: 17px;
    }
}
</style>
</head>

<body>

<header class="school-header">
    <div class="container">
        <div class="school-brand">

            <img
                src="uploads/sfxc.webp"
                alt="St. Francis Xavier College Logo"
                class="school-logo"
            >

            <div>
                <h1 class="school-name">
                    ST. FRANCIS XAVIER COLLEGE
                </h1>

                <p class="school-location">
                    SAN FRANCISCO, AGUSAN DEL SUR
                </p>

                <p class="system-label">GATE GUARD</p>
            </div>

        </div>
    </div>
</header>

<main class="page-container">

    <h2 class="page-title">Student Registration</h2>

    <section class="registration-card">

        <div class="card-heading">
            <h2>Register New Student</h2>
        </div>

        <div class="card-body">

            <?= $message ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="rfid-box mb-4">

                    <h4 class="text-center">RFID Card Registration</h4>

                    <p class="text-center mb-3">
                        Tap the student's RFID card on the scanner.
                    </p>

                    <input
                        type="text"
                        id="rfid_uid"
                        name="rfid_uid"
                        placeholder="Waiting for RFID..."
                        readonly
                        required
                    >

                    <div id="rfid_status" class="text-center text-success">
                        Waiting for RFID card...
                    </div>

                </div>

                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        class="form-control"
                        placeholder="Enter Student ID"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        class="form-control"
                        placeholder="Enter Full Name"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="course" class="form-label">Course</label>
                    <input
                        type="text"
                        id="course"
                        name="course"
                        class="form-control"
                        placeholder="Enter Course"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="section" class="form-label">Section</label>
                    <input
                        type="text"
                        id="section"
                        name="section"
                        class="form-control"
                        placeholder="Enter Section"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="parent_phone" class="form-label">
                        Parent Phone Number
                    </label>

                    <input
                        type="tel"
                        id="parent_phone"
                        name="parent_phone"
                        class="form-control"
                        placeholder="Enter Parent Phone Number"
                        maxlength="20"
                        required
                    >

                    <small class="text-muted">
                        Example: 09462210532
                    </small>
                </div>

                <div class="mb-4">
                    <label for="photo" class="form-label">
                        Student Photo (Optional)
                    </label>

                    <input
                        type="file"
                        id="photo"
                        name="photo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="text-muted">
                        JPG, JPEG, PNG, or WEBP. Maximum 5MB.
                    </small>
                </div>

                <div class="d-grid gap-2">

                    <button
                        type="submit"
                        name="save"
                        class="btn btn-success btn-lg"
                    >
                        Save Student
                    </button>

                    <a href="students.php"
                       class="btn btn-outline-secondary btn-lg">
                        View Students
                    </a>

                    <a href="index.php"
                       class="btn btn-outline-secondary btn-lg">
                        Dashboard
                    </a>

                </div>

            </form>

        </div>
    </section>

    <footer class="footer">
        <strong>ST. FRANCIS XAVIER COLLEGE</strong><br>
        GATE GUARD · RFID STUDENT ATTENDANCE MONITORING SYSTEM<br>
        &copy; 2026 All rights reserved.
    </footer>

</main>

<script>
let lastRFIDId = 0;

function checkRFID() {
    fetch("get_rfid.php?time=" + Date.now())
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                return;
            }

            const scanId = parseInt(data.id, 10);

            if (scanId > lastRFIDId) {
                lastRFIDId = scanId;

                const rfidInput = document.getElementById("rfid_uid");
                const status = document.getElementById("rfid_status");

                rfidInput.value = data.uid;
                rfidInput.classList.add("rfid-detected");

                status.textContent = "RFID detected: " + data.uid;

                setTimeout(function() {
                    status.textContent =
                        "RFID ready. You can register this student.";
                }, 3000);
            }
        })
        .catch(error => {
            console.error("RFID Error:", error);
        });
}

setInterval(checkRFID, 500);
</script>

</body>
</html>
