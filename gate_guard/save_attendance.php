<?php

include("db.php");

header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");


// =====================================================
// SMS GATEWAY SETTINGS
// =====================================================

$sms_gateway_url = "http://192.168.7.140:8082/";

// PUT YOUR NEW LOCAL SERVICE TOKEN HERE
$sms_gateway_token = "1263af20-2331-413d-9e74-05f76e27859e";


// =====================================================
// CHECK DATABASE
// =====================================================

if (!$conn) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();

}


// =====================================================
// GET RFID UID
// =====================================================

if (!isset($_POST['rfid_uid'])) {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID missing."
    ]);

    exit();

}

$rfid_uid = trim($_POST['rfid_uid']);


// =====================================================
// CHECK EMPTY UID
// =====================================================

if ($rfid_uid === "") {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID is empty."
    ]);

    exit();

}


// =====================================================
// SECURE UID
// =====================================================

$rfid_uid = mysqli_real_escape_string(
    $conn,
    $rfid_uid
);


// =====================================================
// FIND STUDENT
// =====================================================

$sql_student = "
    SELECT
        id,
        student_id,
        fullname,
        course,
        section,
        rfid_uid,
        parent_phone,
        photo
    FROM students
    WHERE UPPER(TRIM(rfid_uid)) = UPPER('$rfid_uid')
    LIMIT 1
";

$result = mysqli_query(
    $conn,
    $sql_student
);


// =====================================================
// DATABASE ERROR
// =====================================================

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();

}


// =====================================================
// RFID NOT REGISTERED
// =====================================================

if (mysqli_num_rows($result) == 0) {

    echo json_encode([
        "success" => false,
        "message" => "RFID is not registered!",
        "rfid_uid" => $rfid_uid
    ]);

    exit();

}


// =====================================================
// GET STUDENT
// =====================================================

$student = mysqli_fetch_assoc($result);


// =====================================================
// STUDENT DATA
// =====================================================

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


// =====================================================
// PARENT PHONE
// =====================================================

$parent_phone = isset($student['parent_phone'])
    ? trim($student['parent_phone'])
    : "";


// =====================================================
// CURRENT DATE AND TIME
// =====================================================

$current_date = date("Y-m-d");

$current_time = date("H:i:s");


// =====================================================
// CHECK LAST ATTENDANCE TODAY
// =====================================================

$sql_last = "
    SELECT
        id,
        status,
        date,
        time
    FROM attendance
    WHERE student_id = '$student_id'
    AND date = '$current_date'
    ORDER BY id DESC
    LIMIT 1
";

$last_result = mysqli_query(
    $conn,
    $sql_last
);


// =====================================================
// ATTENDANCE CHECK ERROR
// =====================================================

if (!$last_result) {

    echo json_encode([
        "success" => false,
        "message" => "Attendance check error: " . mysqli_error($conn)
    ]);

    exit();

}


// =====================================================
// DEFAULT STATUS = IN
// =====================================================

$status = "IN";


// =====================================================
// DETERMINE IN / OUT
// =====================================================

if (mysqli_num_rows($last_result) > 0) {

    $last_record = mysqli_fetch_assoc(
        $last_result
    );

    $last_status = strtoupper(
        trim($last_record['status'])
    );

    if ($last_status === "IN") {

        $status = "OUT";

    } else {

        $status = "IN";

    }

}


// =====================================================
// SAVE ATTENDANCE
// =====================================================

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

$insert_result = mysqli_query(
    $conn,
    $sql_insert
);


// =====================================================
// SAVE ERROR
// =====================================================

if (!$insert_result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to save attendance: " . mysqli_error($conn)
    ]);

    exit();

}


// =====================================================
// SMS VARIABLES
// =====================================================

$sms_sent = false;
$sms_message = "";

$sms_response = "";
$curl_error = "";
$http_code = 0;


// =====================================================
// FORMAT PHONE NUMBER
// =====================================================

if ($parent_phone !== "") {

    // Remove spaces, dashes and parentheses
    $parent_phone = preg_replace(
        '/[\s\-\(\)]/',
        '',
        $parent_phone
    );


    // 09XXXXXXXXX → +639XXXXXXXXX

    if (preg_match('/^09[0-9]{9}$/', $parent_phone)) {

        $parent_phone = "+63" . substr(
            $parent_phone,
            1
        );

    }


    // 9XXXXXXXXX → +639XXXXXXXXX

    elseif (preg_match('/^9[0-9]{9}$/', $parent_phone)) {

        $parent_phone = "+63" . $parent_phone;

    }

}


// =====================================================
// SEND SMS
// =====================================================

if ($parent_phone !== "") {

    $sms_text =
        "Gate Guard Alert: " .
        $fullname .
        " has recorded " .
        $status .
        " at " .
        date("h:i A", strtotime($current_time)) .
        " on " .
        date("M d, Y", strtotime($current_date)) .
        ".";


    // =================================================
    // CREATE JSON
    // =================================================

    $sms_data = json_encode([

        "to" => $parent_phone,

        "message" => $sms_text

    ]);


    // =================================================
    // INITIALIZE CURL
    // =================================================

    $ch = curl_init(
        $sms_gateway_url
    );


    // =================================================
    // POST
    // =================================================

    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    // =================================================
    // JSON BODY
    // =================================================

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        $sms_data
    );


    // =================================================
    // HEADERS
    // =================================================

    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [

            "Authorization: " . $sms_gateway_token,

            "Content-Type: application/json"

        ]
    );


    // =================================================
    // RETURN RESPONSE
    // =================================================

    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    // =================================================
    // TIMEOUT
    // =================================================

    curl_setopt(
        $ch,
        CURLOPT_CONNECTTIMEOUT,
        3
    );

    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        5
    );


    // =================================================
    // EXECUTE
    // =================================================

    $sms_response = curl_exec($ch);


    // =================================================
    // CURL ERROR
    // =================================================

    $curl_error = curl_error($ch);


    // =================================================
    // HTTP CODE
    // =================================================

    $http_code = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


    // =================================================
    // CLOSE CURL
    // =================================================

    curl_close($ch);


    // =================================================
    // SMS SUCCESS
    // =================================================

    if (
        $curl_error === "" &&
        $http_code >= 200 &&
        $http_code < 300
    ) {

        $sms_sent = true;

        $sms_message = "SMS sent successfully.";

    }


    // =================================================
    // SMS FAILED
    // =================================================

    else {

        $sms_sent = false;

        $sms_message =
            "Attendance saved, but SMS was not sent.";

    }

}


// =====================================================
// NO PARENT PHONE
// =====================================================

else {

    $sms_message =
        "Attendance saved. No parent phone number registered.";

}


// =====================================================
// FINAL RESPONSE
// =====================================================

echo json_encode([

    "success" => true,

    "message" => "Attendance saved successfully!",

    "student_id" => $student['student_id'],

    "fullname" => $student['fullname'],

    "course" => $student['course'],

    "section" => $student['section'],

    "rfid_uid" => $student['rfid_uid'],

    "photo" => isset($student['photo'])
        ? $student['photo']
        : "",

    "date" => $current_date,

    "time" => $current_time,

    "status" => $status,

    "sms_sent" => $sms_sent,

    "sms_message" => $sms_message,

    // =================================================
    // TEMPORARY DEBUG INFORMATION
    // =================================================

    "sms_http_code" => $http_code,

    "sms_response" => $sms_response,

    "sms_error" => $curl_error,

    "parent_phone" => $parent_phone

]);

?>