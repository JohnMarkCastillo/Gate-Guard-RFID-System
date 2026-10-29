
<?php
require_once "db.php";

mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$countResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM admin_users"
);

$countRow = mysqli_fetch_assoc($countResult);
$alreadySetup = ((int)$countRow["total"] > 0);

$error = "";
$success = "";

if (!$alreadySetup && $_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
        $error = "Username: 3 hanggang 60 characters lamang.";
    } elseif (strlen($password) < 8) {
        $error = "Ang password ay dapat may 8 characters pataas.";
    } elseif ($password !== $confirm) {
        $error = "Hindi magkapareho ang mga password.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO admin_users (username, password_hash)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "ss", $username, $hash);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Nagawa na ang unang admin account.";
            $alreadySetup = true;
        } else {
            $error = "Hindi nagawa ang account. Baka ginagamit na ang username.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="tl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>First Admin Setup</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #edf2f7;
    margin: 0;
    padding: 20px;
}
main {
    max-width: 480px;
    margin: 40px auto;
    background: white;
    padding: 28px;
    border-radius: 12px;
}
label {
    display: block;
    margin-top: 14px;
    font-weight: bold;
}
input {
    width: 100%;
    padding: 11px;
    margin-top: 6px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    box-sizing: border-box;
}
button {
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    background: #15803d;
    color: white;
    border: none;
    border-radius: 7px;
    font-weight: bold;
    cursor: pointer;
}
.error {
    padding: 10px;
    background: #fee2e2;
    color: #991b1b;
}
.success {
    padding: 10px;
    background: #dcfce7;
    color: #166534;
}
</style>
</head>
<body>
<main>
<h2>Gate Guard — First Admin Setup</h2>

<?php if ($alreadySetup && $success === ""): ?>

    <p>May admin account na. Hindi na puwedeng gumawa ng unang account dito.</p>
    <a href="login.php">Pumunta sa login</a>

<?php else: ?>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>

        <p class="success">
            <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
        </p>
        <p>Burahin ang setup_admin.php pagkatapos gumawa ng account.</p>
        <a href="login.php">Pumunta sa login</a>

    <?php else: ?>

        <p>Gumawa ng unang admin username at password.</p>

        <form method="POST">
            <label>Username</label>
            <input
                type="text"
                name="username"
                minlength="3"
                maxlength="60"
                pattern="[A-Za-z0-9_.-]+"
                required
            >

            <label>Password (minimum 8 characters)</label>
            <input
                type="password"
                name="password"
                minlength="8"
                required
            >

            <label>Ulitin ang password</label>
            <input
                type="password"
                name="confirm_password"
                minlength="8"
                required
            >

            <button type="submit">CREATE FIRST ADMIN</button>
        </form>

    <?php endif; ?>
<?php endif; ?>

</main>
</body>
</html>
