<?php
// Mencegah timeout jika dijalankan melalui CLI atau browser
set_time_limit(0);
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';
$dbName = 'crypto_db';

$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

/**
 * Fungsi untuk mengambil data dari endpoint Public API Indodax
 */
function fetchIndodaxAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CryptoPatternEngine/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Loop eksekusi otomatis tiap 30 detik
while (true) {
    $startTime = time();
    
    // Endpoint Ticker All Indodax
    $apiUrl = 'https://indodax.com/api/ticker_all';
    $data = fetchIndodaxAPI($apiUrl);

    if (isset($data['tickers']) && is_array($data['tickers'])) {
        $serverTime = $data['tickers']['btc_idr']['server_time'] ?? time();

        $stmt = $pdo->prepare("
            INSERT INTO crypto_patterns 
            (pair, high, low, last, r, tr, lr, ptr, plr, server_time) 
            VALUES (:pair, :high, :low, :last, :r, :tr, :lr, :ptr, :plr, :server_time)
        ");

        $pdo->beginTransaction();

        foreach ($data['tickers'] as $pair => $ticker) {
            $H = (float)($ticker['high'] ?? 0);
            $L = (float)($ticker['low'] ?? 0);
            $C = (float)($ticker['last'] ?? 0);

            // Formulasi Sesuai Rumus Paper:
            // R = H - L
            // TR = H - C
            // LR = C - L
            $R  = $H - $L;
            $TR = $H - $C;
            $LR = $C - $L;

            // PTR = (TR / R) * 100%
            // PLR = (LR / R) * 100%
            // Validasi pembagian dengan 0 (jika High == Low)
            if ($R > 0) {
                $PTR = ($TR / $R) * 100;
                $PLR = ($LR / $R) * 100;
            } else {
                $PTR = 0;
                $PLR = 0;
            }

            $stmt->execute([
                ':pair'        => $pair,
                ':high'        => $H,
                ':low'         => $L,
                ':last'        => $C,
                ':r'           => $R,
                ':tr'          => $TR,
                ':lr'          => $LR,
                ':ptr'         => round($PTR, 4),
                ':plr'         => round($PLR, 4),
                ':server_time' => $serverTime
            ]);
        }

        $pdo->commit();
        echo "[" . date('Y-m-d H:i:s') . "] Sukses memproses & menyimpan seluruh ticker ke database.\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Gagal mengambil data atau respon tidak valid.\n";
    }

    // Interval refresh 30 detik
    sleep(30);
}