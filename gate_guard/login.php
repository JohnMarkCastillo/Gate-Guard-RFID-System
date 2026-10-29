<?php
session_start();
require_once "db.php";

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if (!empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, password_hash
             FROM admin_users
             WHERE username = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($admin && password_verify(
            $password,
            $admin["password_hash"]
        )) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = (int)$admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: index.php");
            exit;
        }

        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | Gate Guard</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 24px;
    font-family: Arial, Helvetica, sans-serif;
    background: linear-gradient(135deg, #e8f5e9, #f8fafc);
}

.login-box {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
}

.school-header {
    text-align: center;
    margin-bottom: 25px;
}

.school-logo {
    width: 115px;
    height: 115px;
    object-fit: contain;
    display: block;
    margin: 0 auto 15px;
}

.school-name {
    color: #14532d;
    font-size: 23px;
    font-weight: 800;
    line-height: 1.35;
    margin: 0;
}

.school-location {
    color: #64748b;
    font-size: 12px;
    letter-spacing: 1px;
    margin: 8px 0 0;
}

.divider {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 22px 0;
}

.system-name {
    text-align: center;
    color: #166534;
    font-size: 27px;
    font-weight: 800;
    letter-spacing: 2px;
    margin: 0 0 6px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    font-size: 14px;
    margin: 0 0 25px;
}

label {
    display: block;
    margin: 16px 0 7px;
    font-size: 14px;
    font-weight: bold;
    color: #334155;
}

input {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
    border-color: #15803d;
    box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.12);
}

button {
    width: 100%;
    margin-top: 25px;
    padding: 14px;
    border: none;
    border-radius: 9px;
    background: #15803d;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}

button:hover {
    background: #166534;
}

.error {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: #fee2e2;
    color: #991b1b;
    font-size: 14px;
}

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 25px;
    line-height: 1.6;
}

@media (max-width: 480px) {
    .login-box {
        padding: 25px 20px;
    }

    .school-name {
        font-size: 20px;
    }

    .system-name {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<div class="login-box">

    <div class="school-header">

        <img
            src="uploads/sfxc.webp"
            alt="St. Francis Xavier College Logo"
            class="school-logo"
>
        >

        <h1 class="school-name">
            ST. FRANCIS XAVIER COLLEGE
        </h1>

        <p class="school-location">
            SAN FRANCISCO, AGUSAN DEL SUR
        </p>

    </div>

    <hr class="divider">

    <h2 class="system-name">GATE GUARD</h2>

    <p class="subtitle">
       <strong>ADMIN LOGIN</strong>
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            maxlength="60"
            autocomplete="username"
            placeholder="Enter admin username"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            placeholder="Enter admin password"
            required
        >

        <button type="submit">LOGIN</button>

    </form>

    <div class="footer">
        ST. FRANCIS XAVIER COLLEGE<br>
        GATE GUARD SECURITY AND ATTENDANCE SYSTEM
    </div>

</div>

</body>
</html>