<?php

include("db.php");

header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

// Make sure RFID UID was received
if (!isset($_POST["rfid_uid"])) {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID not received"
    ]);

    exit;
}

// Clean RFID UID
$uid = strtoupper(trim($_POST["rfid_uid"]));

if ($uid == "") {

    echo json_encode([
        "success" => false,
        "message" => "RFID UID is empty"
    ]);

    exit;
}

// Escape for database
$uid_safe = mysqli_real_escape_string($conn, $uid);

// Insert the new RFID scan
$sql = "
    INSERT INTO rfid_scans (uid)
    VALUES ('$uid_safe')
";

$result = mysqli_query($conn, $sql);

if ($result) {

    echo json_encode([
        "success" => true,
        "message" => "RFID scan saved",
        "id" => mysqli_insert_id($conn),
        "uid" => $uid
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
}

exit;

?>