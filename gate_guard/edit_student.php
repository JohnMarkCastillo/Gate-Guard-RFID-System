<?php
require_once "auth.php";
require_once "db.php";

date_default_timezone_set("Asia/Manila");

$message = "";
$messageType = "success";

// ==========================================
// GET STUDENT ID
// ==========================================
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: students.php");
    exit();
}

$id = (int) $_GET["id"];

// ==========================================
// GET STUDENT DATA
// ==========================================
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    http_response_code(404);
    exit("Student not found.");
}

// ==========================================
// UPDATE STUDENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update"])) {

    $student_id = trim($_POST["student_id"] ?? "");
    $fullname = trim($_POST["fullname"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $section = trim($_POST["section"] ?? "");
    $rfid_uid = strtoupper(trim($_POST["rfid_uid"] ?? ""));
    $parent_phone = trim($_POST["parent_phone"] ?? "");

    if (
        $student_id === "" ||
        $fullname === "" ||
        $course === "" ||
        $section === "" ||
        $rfid_uid === "" ||
        $parent_phone === ""
    ) {
        $message = "Please complete all required fields.";
        $messageType = "danger";
    } else {

        // Normalize Philippine mobile number
        $parent_phone = preg_replace('/[\s\-\(\)]/', '', $parent_phone);

        if (preg_match('/^09[0-9]{9}$/', $parent_phone)) {
            $parent_phone = "+63" . substr($parent_phone, 1);
        } elseif (preg_match('/^9[0-9]{9}$/', $parent_phone)) {
            $parent_phone = "+63" . $parent_phone;
        }

        $oldPhoto = $student["photo"] ?? "";
        $photoName = $oldPhoto;
        $newPhotoPath = "";
        $photoError = "";

        // ======================================
        // OPTIONAL NEW STUDENT PHOTO
        // ======================================
        if (
            isset($_FILES["photo"]) &&
            $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
                $photoError = "Photo upload failed. Please try again.";
            } else {
                $tmpFile = $_FILES["photo"]["tmp_name"];
                $fileSize = (int) $_FILES["photo"]["size"];
                $extension = strtolower(
                    pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION)
                );

                $allowed = ["jpg", "jpeg", "png", "webp"];

                if (!in_array($extension, $allowed, true)) {
                    $photoError = "Invalid photo format. Use JPG, JPEG, PNG, or WEBP.";
                } elseif ($fileSize > 5000000) {
                    $photoError = "Photo is too large. Maximum size is 5MB.";
                } elseif (
                    !is_uploaded_file($tmpFile) ||
                    @getimagesize($tmpFile) === false
                ) {
                    $photoError = "Please upload a valid image.";
                } else {
                    if (!is_dir(__DIR__ . "/uploads")) {
                        if (!mkdir(__DIR__ . "/uploads", 0755, true)) {
                            $photoError = "Unable to create the uploads folder.";
                        }
                    }

                    if ($photoError === "") {
                        try {
                            $photoName = time() . "_" .
                                bin2hex(random_bytes(8)) . "." . $extension;
                        } catch (Exception $e) {
                            $photoName = time() . "_" .
                                uniqid() . "." . $extension;
                        }

                        $newPhotoPath = __DIR__ . "/uploads/" . $photoName;

                        if (!move_uploaded_file($tmpFile, $newPhotoPath)) {
                            $photoError = "Failed to upload the new photo.";
                            $photoName = $oldPhoto;
                            $newPhotoPath = "";
                        }
                    }
                }
            }
        }

        if ($photoError !== "") {
            $message = $photoError;
            $messageType = "danger";
        } else {
            // ======================================
            // UPDATE DATABASE USING PREPARED QUERY
            // ======================================
            $update = mysqli_prepare(
                $conn,
                "UPDATE students
                 SET student_id = ?,
                     fullname = ?,
                     course = ?,
                     section = ?,
                     rfid_uid = ?,
                     parent_phone = ?,
                     photo = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "sssssssi",
                $student_id,
                $fullname,
                $course,
                $section,
                $rfid_uid,
                $parent_phone,
                $photoName,
                $id
            );

            if (mysqli_stmt_execute($update)) {
                mysqli_stmt_close($update);

                // Delete old photo only after successful database update
                if (
                    $newPhotoPath !== "" &&
                    !empty($oldPhoto) &&
                    basename($oldPhoto) !== basename($photoName)
                ) {
                    $oldPhotoPath = __DIR__ . "/uploads/" . basename($oldPhoto);

                    if (is_file($oldPhotoPath)) {
                        @unlink($oldPhotoPath);
                    }
                }

                $message = "Student information updated successfully!";
                $messageType = "success";

                // Reload updated student
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT * FROM students WHERE id = ? LIMIT 1"
                );
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $student = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

            } else {
                $dbError = mysqli_error($conn);
                mysqli_stmt_close($update);

                // Remove new upload if database update failed
                if ($newPhotoPath !== "" && is_file($newPhotoPath)) {
                    @unlink($newPhotoPath);
                }

                error_log("Edit student database error: " . $dbError);
                $message = "Unable to update student information. Please check the database.";
                $messageType = "danger";
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Safely display the current student photo
$photoFile = basename($student["photo"] ?? "");
$photoPath = __DIR__ . "/uploads/" . $photoFile;
$hasPhoto = $photoFile !== "" && is_file($photoPath);
$photoUrl = "uploads/" . rawurlencode($photoFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Student | Gate Guard</title>

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
    --gold: #d4a72c;
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

.edit-card {
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
    border-bottom: 4px solid var(--gold);
}

.card-heading h2 {
    margin: 0;
    font-size: 22px;
    font-weight: bold;
}

.card-body {
    padding: 25px;
}

.photo-panel {
    padding: 24px;
    margin-bottom: 25px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: #f7fcf8;
    text-align: center;
}

.photo-panel h5 {
    margin-bottom: 18px;
    color: var(--dark-green);
    font-weight: 800;
}

.current-photo,
.no-photo {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    margin: 0 auto;
}

.current-photo {
    display: block;
    object-fit: cover;
    border: 5px solid var(--green);
    background: white;
}

.no-photo {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 5px solid #dbe5dd;
    background: #e9ecef;
    font-size: 65px;
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

    .photo-panel {
        padding: 17px;
    }

    .current-photo,
    .no-photo {
        width: 150px;
        height: 150px;
    }
}
</style>
</head>

<body>

<header class="school-header">
    <div class="container">
        <div class="school-brand">

            <!-- SAME SFXC WEBP LOGO AS register.php -->
            <img
                src="uploads/sfxc.webp"
                alt="St. Francis Xavier College Logo"
                class="school-logo"
            >

            <div>
                <h1 class="school-name">ST. FRANCIS XAVIER COLLEGE</h1>
                <p class="school-location">SAN FRANCISCO, AGUSAN DEL SUR</p>
                <p class="system-label">GATE GUARD</p>
            </div>

        </div>
    </div>
</header>

<main class="page-container">

    <h2 class="page-title">Edit Student</h2>

    <section class="edit-card">

        <div class="card-heading">
            <h2>Edit Student Information</h2>
        </div>

        <div class="card-body">

            <?php if ($message !== ""): ?>
                <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show" role="alert">
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="photo-panel">
                    <h5>Current Student Photo</h5>

                    <?php if ($hasPhoto): ?>
                        <img
                            src="<?= e($photoUrl) ?>?v=<?= @filemtime($photoPath) ?>"
                            class="current-photo"
                            alt="Student Photo"
                        >
                    <?php else: ?>
                        <div class="no-photo" aria-label="No student photo">👤</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="photo" class="form-label">Change Student Photo (Optional)</label>

                    <input
                        type="file"
                        id="photo"
                        name="photo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="text-muted">
                        Choose a new photo only if you want to replace the current one.
                        JPG, JPEG, PNG, or WEBP — maximum 5MB.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        class="form-control"
                        value="<?= e($student["student_id"]) ?>"
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
                        value="<?= e($student["fullname"]) ?>"
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
                        value="<?= e($student["course"]) ?>"
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
                        value="<?= e($student["section"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="parent_phone" class="form-label">Parent Phone Number</label>
                    <input
                        type="tel"
                        id="parent_phone"
                        name="parent_phone"
                        class="form-control"
                        value="<?= e($student["parent_phone"] ?? "") ?>"
                        placeholder="09XXXXXXXXX"
                        maxlength="20"
                        required
                    >
                    <small class="text-muted">Example: 09462210532</small>
                </div>

                <div class="mb-4">
                    <label for="rfid_uid" class="form-label">RFID UID</label>
                    <input
                        type="text"
                        id="rfid_uid"
                        name="rfid_uid"
                        class="form-control"
                        value="<?= e($student["rfid_uid"]) ?>"
                        required
                    >
                </div>

                <div class="d-grid gap-2">

                    <button type="submit" name="update" class="btn btn-success btn-lg">
                        Save Changes
                    </button>

                    <a href="students.php" class="btn btn-outline-secondary btn-lg">
                        Back to Students
                    </a>

                    <a href="index.php" class="btn btn-outline-secondary btn-lg">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>