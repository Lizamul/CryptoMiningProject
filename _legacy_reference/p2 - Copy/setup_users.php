<?php

$config = require __DIR__ . '/config.php';

$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$users = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'role' => 'admin',
    ],
    [
        'username' => 'client',
        'password' => 'client123',
        'role' => 'client',
    ],
];

foreach ($users as $user) {

    $check = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = ?
    ");

    $check->execute([$user['username']]);

    if ($check->fetch()) {
        echo "{$user['username']} already exists.<br>";
        continue;
    }

    $passwordHash = password_hash(
        $user['password'],
        PASSWORD_DEFAULT
    );

    $insert = $pdo->prepare("
        INSERT INTO users (
            username,
            password,
            role
        )
        VALUES (?, ?, ?)
    ");

    $insert->execute([
        $user['username'],
        $passwordHash,
        $user['role'],
    ]);

    echo "{$user['username']} created successfully.<br>";
}

echo '<br>Setup finished.';