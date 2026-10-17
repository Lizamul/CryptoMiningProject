<?php
// get_data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'crypto_db';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // created_at menunjukkan kapan batch benar-benar masuk ke database.
    $stmtTime = $pdo->query("SELECT MAX(created_at) AS latest_created_at FROM crypto_patterns");
    $latestCreatedAt = $stmtTime->fetchColumn();

    if (!$latestCreatedAt) {
        echo json_encode(['status' => 'empty', 'data' => []]);
        exit;
    }

    // Ambil semua pair pada batch insert terakhir.
    $stmt = $pdo->prepare("SELECT pair, high, low, last, r, tr, lr, ptr, plr, server_time, created_at 
                           FROM crypto_patterns 
                           WHERE created_at = :created_at 
                           ORDER BY pair ASC");
    $stmt->execute([':created_at' => $latestCreatedAt]);
    $results = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'created_at' => $latestCreatedAt,
        'count' => count($results),
        'data' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi atau query database gagal.',
        'detail' => $e->getMessage()
    ]);
}