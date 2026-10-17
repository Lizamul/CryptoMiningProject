<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die('Access denied.');
    }
}