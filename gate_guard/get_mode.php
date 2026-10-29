<?php

include("db.php");

$result = mysqli_query(
    $conn,
    "SELECT mode FROM system_mode WHERE id=1 LIMIT 1"
);

$row = mysqli_fetch_assoc($result);

echo $row['mode'];

?>