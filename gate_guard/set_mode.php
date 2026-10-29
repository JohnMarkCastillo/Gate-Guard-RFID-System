<?php

include("db.php");

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'attendance';

if($mode != 'register' && $mode != 'attendance'){
    $mode = 'attendance';
}

$mode = mysqli_real_escape_string($conn, $mode);

mysqli_query(
    $conn,
    "UPDATE system_mode SET mode='$mode' WHERE id=1"
);

echo "OK";
?>