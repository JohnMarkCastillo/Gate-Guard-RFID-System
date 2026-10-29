<?php

include("db.php");

header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");


// ======================================
// CHECK DATABASE
// ======================================

if (!$conn) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();
}


// ======================================
// GET RFID UID
// ======================================

if (!isset($_POST['rfid_uid'])) {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID missing."
    ]);

    exit();
}


$rfid_uid = trim($_POST['rfid_uid']);


// ======================================
// CHECK EMPTY UID
// ======================================

if ($rfid_uid === "") {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID is empty."
    ]);

    exit();
}


// ======================================
// SECURE UID
// ======================================

$rfid_uid = mysqli_real_escape_string(
    $conn,
    $rfid_uid
);


// ======================================
// FIND STUDENT
// ======================================

$sql = "
    SELECT
        id,
        student_id,
        fullname,
        course,
        section,
        rfid_uid,
        photo
    FROM students
    WHERE UPPER(TRIM(rfid_uid)) = UPPER('$rfid_uid')
    LIMIT 1
";

$result = mysqli_query($conn, $sql);


// ======================================
// DATABASE ERROR
// ======================================

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}


// ======================================
// RFID NOT REGISTERED
// ======================================

if (mysqli_num_rows($result) == 0) {

    // Save RFID for registration page

    $check_scan = mysqli_query(
        $conn,
        "SELECT id
         FROM rfid_scans
         WHERE uid='$rfid_uid'
         LIMIT 1"
    );

    if (mysqli_num_rows($check_scan) == 0) {

        mysqli_query(
            $conn,
            "INSERT INTO rfid_scans(uid)
             VALUES('$rfid_uid')"
        );
    }


    echo json_encode([

        "success" => false,

        "registered" => false,

        "message" => "RFID is not registered.",

        "rfid_uid" => $rfid_uid

    ]);

    exit();
}


// ======================================
// GET STUDENT
// ======================================

$student = mysqli_fetch_assoc($result);


// ======================================
// STUDENT DATA
// ======================================

$student_id = mysqli_real_escape_string(
    $conn,
    $student['student_id']
);

$fullname = mysqli_real_escape_string(
    $conn,
    $student['fullname']
);

$course = mysqli_real_escape_string(
    $conn,
    $student['course']
);

$section = mysqli_real_escape_string(
    $conn,
    $student['section']
);


// ======================================
// CURRENT DATE / TIME
// ======================================

$current_date = date("Y-m-d");

$current_time = date("H:i:s");


// ======================================
// CHECK LAST ATTENDANCE TODAY
// ======================================

$sql_last = "
    SELECT
        id,
        status,
        date,
        time
    FROM attendance
    WHERE student_id='$student_id'
    AND date='$current_date'
    ORDER BY id DESC
    LIMIT 1
";

$last_result = mysqli_query(
    $conn,
    $sql_last
);


if (!$last_result) {

    echo json_encode([
        "success" => false,
        "message" => "Attendance check error: "
            . mysqli_error($conn)
    ]);

    exit();
}


// ======================================
// DEFAULT = IN
// ======================================

$status = "IN";


// ======================================
// DETERMINE IN / OUT
// ======================================

if (mysqli_num_rows($last_result) > 0) {

    $last_record =
        mysqli_fetch_assoc($last_result);

    $last_status =
        strtoupper(
            trim($last_record['status'])
        );

    if ($last_status === "IN") {

        $status = "OUT";

    } else {

        $status = "IN";
    }
}


// ======================================
// SAVE ATTENDANCE
// ======================================

$sql_insert = "
    INSERT INTO attendance
    (
        student_id,
        fullname,
        course,
        section,
        date,
        time,
        status
    )
    VALUES
    (
        '$student_id',
        '$fullname',
        '$course',
        '$section',
        '$current_date',
        '$current_time',
        '$status'
    )
";

$insert_result =
    mysqli_query(
        $conn,
        $sql_insert
    );


if (!$insert_result) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to save attendance: "
            . mysqli_error($conn)
    ]);

    exit();
}


// ======================================
// SUCCESS
// ======================================

echo json_encode([

    "success" => true,

    "registered" => true,

    "message" =>
        "Attendance saved successfully!",

    "student_id" =>
        $student['student_id'],

    "fullname" =>
        $student['fullname'],

    "course" =>
        $student['course'],

    "section" =>
        $student['section'],

    "rfid_uid" =>
        $student['rfid_uid'],

    "photo" =>
        isset($student['photo'])
        ? $student['photo']
        : "",

    "date" =>
        $current_date,

    "time" =>
        $current_time,

    "status" =>
        $status

]);

?>