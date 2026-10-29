<?php
require_once "auth.php";
require_once "db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Refresh the page and try again.";
    } else {
        $action = $_POST["action"] ?? "";

        if ($action === "add") {
            $username = trim($_POST["username"] ?? "");
            $password = $_POST["password"] ?? "";

            if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
                $error = "Username must be 3-60 characters.";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO admin_users (username, password_hash)
                     VALUES (?, ?)"
                );

                mysqli_stmt_bind_param($stmt, "ss", $username, $hash);

                if (mysqli_stmt_execute($stmt)) {
                    $message = "Admin account added successfully.";
                } elseif (mysqli_errno($conn) === 1062) {
                    $error = "That username is already taken.";
                } else {
                    $error = "The account could not be added.";
                }

                mysqli_stmt_close($stmt);
            }

        } elseif ($action === "delete") {
            $deleteId = (int)($_POST["admin_id"] ?? 0);

            $totalResult = mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total FROM admin_users"
            );

            $total = (int)mysqli_fetch_assoc($totalResult)["total"];

            if ($deleteId <= 0) {
                $error = "Invalid admin account.";
            } elseif ($deleteId === (int)$_SESSION["admin_id"]) {
                $error = "You cannot delete your current account.";
            } elseif ($total <= 1) {
                $error = "At least one admin account must remain.";
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM admin_users WHERE id = ?"
                );

                mysqli_stmt_bind_param($stmt, "i", $deleteId);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $message = "Admin account deleted successfully.";
                } else {
                    $error = "Admin account not found.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

$result = mysqli_query(
    $conn,
    "SELECT id, username, created_at
     FROM admin_users
     ORDER BY id ASC"
);

$adminCount = mysqli_num_rows($result);
$currentUsername = $_SESSION["admin_username"] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Admin Accounts | Gate Guard</title>

<style>
:root {
    --green: #15803d;
    --green-dark: #14532d;
    --green-light: #dcfce7;
    --background: #f3f7f5;
    --text: #172b22;
    --muted: #64748b;
    --border: #e2e8f0;
    --white: #ffffff;
    --red: #b91c1c;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--background);
    color: var(--text);
}

button, input {
    font: inherit;
}

.topbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 20px 28px;
}

.topbar-inner {
    max-width: 1120px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 0;
}

.school-logo {
    width: 66px;
    height: 66px;
    object-fit: contain;
    flex-shrink: 0;
}

.school-name {
    margin: 0;
    color: var(--green-dark);
    font-size: 19px;
    font-weight: 800;
    line-height: 1.4;
}

.school-location {
    margin: 5px 0 0;
    font-size: 12px;
    color: var(--muted);
    letter-spacing: 1px;
}

.system-label {
    margin-top: 5px;
    font-size: 12px;
    font-weight: bold;
    color: var(--green);
    letter-spacing: 1px;
}

.container {
    width: min(1120px, calc(100% - 36px));
    margin: 32px auto 50px;
}

.page-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.page-heading h1 {
    margin: 0;
    font-size: clamp(25px, 4vw, 32px);
    color: var(--green-dark);
}

.page-heading p {
    margin: 9px 0 0;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.6;
}

.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    color: var(--muted);
}

.avatar {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--green-light);
    color: var(--green-dark);
    font-weight: bold;
    font-size: 15px;
}

.navigation {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 11px 16px;
    border: 1px solid transparent;
    border-radius: 9px;
    background: var(--green);
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.btn:hover {
    background: var(--green-dark);
}

.btn:active {
    transform: translateY(1px);
}

.btn-outline {
    background: white;
    color: var(--green-dark);
    border-color: #cbd5e1;
}

.btn-outline:hover {
    background: #f0fdf4;
    color: var(--green-dark);
}

.btn-danger {
    background: #fff1f2;
    color: var(--red);
    border-color: #fecdd3;
    padding: 8px 12px;
    min-height: 36px;
    font-size: 13px;
}

.btn-danger:hover {
    background: var(--red);
    color: white;
}

.panel {
    background: var(--white);
    border: 1px solid #e5ece7;
    border-radius: 16px;
    padding: 26px;
    margin-bottom: 22px;
    box-shadow: 0 5px 20px rgba(15, 50, 30, .045);
}

.panel-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
}

.heading-icon {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--green-light);
    color: var(--green-dark);
}

.heading-icon svg {
    width: 23px;
    height: 23px;
}

.panel-heading h2 {
    margin: 0;
    font-size: 20px;
    color: var(--green-dark);
}

.panel-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    align-items: end;
    gap: 16px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: bold;
    color: #334155;
}

.field input {
    width: 100%;
    height: 45px;
    padding: 0 13px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    background: #fff;
    color: var(--text);
    outline: none;
}

.field input:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(21, 128, 61, .12);
}

.form-grid .btn {
    height: 45px;
    white-space: nowrap;
}

.notice {
    padding: 14px 16px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-size: 14px;
    line-height: 1.5;
}

.message {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    color: #166534;
}

.error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.count {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 20px;
    background: var(--green-light);
    color: var(--green-dark);
    font-size: 12px;
    font-weight: bold;
}

.search-box {
    width: 260px;
    max-width: 100%;
    height: 40px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
}

.search-box:focus {
    border-color: var(--green);
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

thead {
    background: #f8faf9;
}

th {
    padding: 14px 13px;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}

td {
    padding: 16px 13px;
    border-top: 1px solid var(--border);
    font-size: 14px;
    vertical-align: middle;
}

tbody tr:hover {
    background: #fbfefc;
}

.account-user {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 145px;
}

.account-avatar {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #ecfdf5;
    color: var(--green-dark);
    font-weight: bold;
    flex-shrink: 0;
}

.account-name {
    font-weight: bold;
    color: #1e293b;
    overflow-wrap: anywhere;
}

.account-role {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 12px;
}

.date-text {
    color: #475569;
    white-space: nowrap;
}

.current-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 20px;
    background: #dcfce7;
    color: #166534;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.current-badge::before {
    content: "";
    width: 7px;
    height: 7px;
    background: #16a34a;
    border-radius: 50%;
}

.footer {
    text-align: center;
    padding: 15px 10px;
    color: #94a3b8;
    font-size: 12px;
    line-height: 1.7;
}

.empty-state {
    padding: 30px;
    text-align: center;
    color: var(--muted);
}

[hidden] {
    display: none !important;
}

@media (max-width: 760px) {
    .topbar {
        padding: 17px;
    }

    .topbar-inner {
        align-items: flex-start;
    }

    .school-logo {
        width: 52px;
        height: 52px;
    }

    .school-name {
        font-size: 15px;
    }

    .school-location {
        font-size: 10px;
        letter-spacing: .4px;
    }

    .system-label {
        font-size: 10px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-grid .btn {
        width: 100%;
    }

    .panel {
        padding: 19px;
    }

    .user-chip {
        padding: 7px 10px;
    }
}

@media (max-width: 480px) {
    .topbar-inner {
        flex-direction: column;
    }

    .container {
        width: calc(100% - 24px);
        margin-top: 23px;
    }

    .page-heading h1 {
        font-size: 25px;
    }

    .panel-heading h2 {
        font-size: 18px;
    }

    .search-box {
        width: 100%;
    }

    th, td {
        padding: 12px 10px;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">

        <div class="brand">
            <img
                class="school-logo"
                src="uploads/sfxc.webp"
                alt="School Logo"
            >

            <div>
                <h2 class="school-name">ST. FRANCIS XAVIER COLLEGE</h2>
                <p class="school-location">
                    SAN FRANCISCO, AGUSAN DEL SUR
                </p>
                <div class="system-label">GATE GUARD</div>
            </div>
        </div>

        <div class="user-chip">
            <span class="avatar">
                <?= htmlspecialchars(
                    strtoupper(substr($currentUsername, 0, 1)),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </span>
            <span>
                Logged in as<br>
                <strong style="color:#14532d">
                    <?= htmlspecialchars($currentUsername, ENT_QUOTES, "UTF-8") ?>
                </strong>
            </span>
        </div>

    </div>
</header>

<main class="container">

    <div class="page-heading">
        <div>
            <h1>Manage Admin Accounts</h1>
            <p>
                Create and manage administrator accounts for the
                Gate Guard attendance system.
            </p>
        </div>
    </div>

    <nav class="navigation">
        <a class="btn" href="index.php">
            <svg width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="m3 10 9-7 9 7"/>
                <path d="M5 9v12h14V9"/>
                <path d="M9 21v-7h6v7"/>
            </svg>
            Dashboard
        </a>

        <a class="btn btn-outline" href="logout.php">
            <svg width="18" height="18" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M10 17l5-5-5-5"/>
                <path d="M15 12H3"/>
                <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>
            </svg>
            Logout
        </a>
    </nav>

    <?php if ($message !== ""): ?>
        <div class="notice message" role="status">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notice error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="panel">

        <div class="panel-heading">
            <div class="heading-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="10" cy="7" r="4"/>
                    <path d="M19 8v6"/>
                    <path d="M22 11h-6"/>
                </svg>
            </div>

            <div>
                <h2>Add Admin Account</h2>
                <p>Create a new administrator login.</p>
            </div>
        </div>

        <form method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["csrf_token"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <input type="hidden" name="action" value="add">

            <div class="form-grid">

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        minlength="3"
                        maxlength="60"
                        pattern="[A-Za-z0-9_.-]+"
                        title="Use 3-60 letters, numbers, dots, underscores, or hyphens."
                        placeholder="Enter username"
                        autocomplete="off"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        placeholder="Minimum 8 characters"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button class="btn" type="submit">
                    <svg width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>
                    Add Admin
                </button>

            </div>
        </form>

    </section>

    <section class="panel">

        <div class="panel-heading">
            <div class="heading-icon">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="10" cy="7" r="4"/>
                    <path d="M20 8v6"/>
                    <path d="M17 11h6"/>
                </svg>
            </div>

            <div>
                <h2>Existing Admin Accounts</h2>
                <p>View and manage registered administrators.</p>
            </div>
        </div>

        <div class="table-toolbar">
            <span class="count">
                <?= $adminCount ?> Admin Account<?= $adminCount === 1 ? "" : "s" ?>
            </span>

            <input
                type="search"
                id="adminSearch"
                class="search-box"
                placeholder="Search admin username..."
                aria-label="Search admin accounts"
            >
        </div>

        <div class="table-wrap">
            <table id="adminTable">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Date Created</th>
                        <th>Account Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($admin = mysqli_fetch_assoc($result)): ?>
                    <tr class="admin-row">
                        <td>
                            <div class="account-user">
                                <div class="account-avatar">
                                    <?= htmlspecialchars(
                                        strtoupper(substr($admin["username"], 0, 1)),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </div>

                                <div>
                                    <span class="account-name">
                                        <?= htmlspecialchars(
                                            $admin["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>
                                    <span class="account-role">Administrator</span>
                                </div>
                            </div>
                        </td>

                        <td class="date-text">
                            <?= htmlspecialchars(
                                $admin["created_at"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </td>

                        <td>
                            <?php if (
                                (int)$admin["id"] === (int)$_SESSION["admin_id"]
                            ): ?>
                                <span class="current-badge">Current account</span>
                            <?php else: ?>
                                <span style="color:#64748b;font-size:13px">
                                    Registered
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (
                                (int)$admin["id"] !== (int)$_SESSION["admin_id"]
                            ): ?>
                                <form
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this admin account?');"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $_SESSION["csrf_token"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input type="hidden" name="action" value="delete">

                                    <input
                                        type="hidden"
                                        name="admin_id"
                                        value="<?= (int)$admin["id"] ?>"
                                    >

                                    <button class="btn btn-danger" type="submit">
                                        <svg width="15" height="15"
                                             viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor"
                                             stroke-width="2"
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             aria-hidden="true">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4h8v2"/>
                                            <path d="m19 6-1 14H6L5 6"/>
                                            <path d="M10 11v5"/>
                                            <path d="M14 11v5"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:#94a3b8;font-size:13px">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

                    <tr id="noResults" hidden>
                        <td colspan="4" class="empty-state">
                            No matching admin accounts found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </section>

    <footer class="footer">
        <strong>ST. FRANCIS XAVIER COLLEGE</strong><br>
        GATE GUARD · RFID STUDENT ATTENDANCE MONITORING SYSTEM
    </footer>

</main>

<script>
const searchInput = document.getElementById("adminSearch");
const rows = document.querySelectorAll(".admin-row");
const noResults = document.getElementById("noResults");

searchInput.addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();
    let visibleCount = 0;

    rows.forEach(function (row) {
        const username = row.querySelector(".account-name")
            .textContent.toLowerCase();

        const matches = username.includes(query);

        row.hidden = !matches;

        if (matches) {
            visibleCount++;
        }
    });

    noResults.hidden = visibleCount !== 0;
});
</script>

</body>
</html>