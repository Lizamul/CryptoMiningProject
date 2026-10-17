<?php

session_start();

$config = require __DIR__ . '/config.php';

$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: client/dashboard.php');
    }

    exit;
}

$error = '';

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Username and password are required.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id, username, password, role
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if (
            $user &&
            password_verify($password, $user['password'])
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: client/dashboard.php');
            }

            exit;

        } else {

            $error = 'Invalid username or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Crypto Pattern Login</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >
</head>

<body class="login-body">

<div class="login-card">

    <h1>Dukun Crypto</h1>

    <p class="muted">
        Login to continue
    </p>

    <?php if ($error): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>Username</label>

            <input
                type="text"
                name="username"
                required
            >

        </div>

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                required
            >

        </div>

        <button
            type="submit"
            class="btn full"
        >
            Login
        </button>

    </form>

</div>

</body>
</html>