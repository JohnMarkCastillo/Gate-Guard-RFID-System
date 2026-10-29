<?php

include("db.php");

header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");


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
// GET LATEST RFID SCAN
// =====================================================

$sql = "
    SELECT
        id,
        uid
    FROM rfid_scans
    ORDER BY id DESC
    LIMIT 1
";


$result = mysqli_query(
    $conn,
    $sql
);


// =====================================================
// DATABASE ERROR
// =====================================================

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);

    exit();
}


// =====================================================
// NO RFID SCAN
// =====================================================

if (mysqli_num_rows($result) == 0) {

    echo json_encode([
        "success" => false,
        "message" => "No RFID scan found."
    ]);

    exit();
}


// =====================================================
// GET RFID DATA
// =====================================================

$row = mysqli_fetch_assoc($result);


// =====================================================
// SUCCESS
// =====================================================

echo json_encode([

    "success" => true,

    "id" => (int)$row["id"],

    "uid" => strtoupper(
        trim($row["uid"])
    )

]);

exit();

?>