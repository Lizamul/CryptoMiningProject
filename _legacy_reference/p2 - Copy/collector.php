<?php

date_default_timezone_set('Asia/Jakarta');

$config = require __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| WEB ACCESS SECURITY
|--------------------------------------------------------------------------
|
| Terminal / Cron:
|     php collector.php
|
| Web:
|     only logged-in Admin is allowed.
|
*/

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    session_start();

    if (
        !isset($_SESSION['user_id']) ||
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
    ) {
        http_response_code(403);
        die('Access denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Collector must be triggered using POST.');
    }
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

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
    finishCollector(false, 'Database connection failed: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| GET INDODAX DATA
|--------------------------------------------------------------------------
*/

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $config['api_url'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,

    // Keep SSL verification enabled
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);

if ($response === false) {
    finishCollector(
        false,
        'cURL Error: ' . curl_error($ch)
    );
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    finishCollector(
        false,
        'Indodax API returned HTTP status ' . $httpCode
    );
}

/*
|--------------------------------------------------------------------------
| DECODE JSON
|--------------------------------------------------------------------------
*/

$data = json_decode($response, true);

if (!is_array($data)) {
    finishCollector(false, 'Invalid JSON response from Indodax.');
}

$tickers = $data['tickers'] ?? $data;

if (!is_array($tickers)) {
    finishCollector(false, 'Ticker data was not found.');
}

/*
|--------------------------------------------------------------------------
| PREPARE INSERT
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO patterned_dataset (
        pair,
        symbol,
        high_price,
        low_price,
        current_price,
        range_value,
        top_range,
        lower_range,
        ptr,
        plr,
        api_server_time,
        collected_at
    )
    VALUES (
        :pair,
        :symbol,
        :high_price,
        :low_price,
        :current_price,
        :range_value,
        :top_range,
        :lower_range,
        :ptr,
        :plr,
        :api_server_time,
        :collected_at
    )
";

$stmt = $pdo->prepare($sql);

$inserted = 0;
$skipped = 0;

$collectedAt = date('Y-m-d H:i:s');

/*
|--------------------------------------------------------------------------
| PROCESS EVERY TICKER
|--------------------------------------------------------------------------
*/

foreach ($tickers as $pair => $ticker) {

    if (!is_array($ticker)) {
        $skipped++;
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL: ONLY IDR PAIRS
    |--------------------------------------------------------------------------
    */

    if ($config['only_idr']) {
        if (!str_ends_with(strtolower($pair), '_idr')) {
            continue;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REQUIRED VALUES
    |--------------------------------------------------------------------------
    */

    if (
        !isset($ticker['high']) ||
        !isset($ticker['low']) ||
        !isset($ticker['last'])
    ) {
        $skipped++;
        continue;
    }

    $high = (float) $ticker['high'];
    $low = (float) $ticker['low'];
    $current = (float) $ticker['last'];

    /*
    |--------------------------------------------------------------------------
    | FORMULAS
    |--------------------------------------------------------------------------
    |
    | R   = H - L
    | TR  = H - C
    | LR  = C - L
    | PTR = TR / R × 100
    | PLR = LR / R × 100
    |
    */

    $range = $high - $low;
    $topRange = $high - $current;
    $lowerRange = $current - $low;

    if ($range == 0.0) {
        $ptr = 0;
        $plr = 0;
    } else {
        $ptr = ($topRange / $range) * 100;
        $plr = ($lowerRange / $range) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | SYMBOL
    |--------------------------------------------------------------------------
    */

    $parts = explode('_', $pair);
    $symbol = strtoupper($parts[0]);

    /*
    |--------------------------------------------------------------------------
    | API SERVER TIME
    |--------------------------------------------------------------------------
    */

    $serverTime =
        $ticker['server_time']
        ?? $data['server_time']
        ?? null;

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    try {
        $stmt->execute([
            ':pair' => strtolower($pair),
            ':symbol' => $symbol,
            ':high_price' => $high,
            ':low_price' => $low,
            ':current_price' => $current,
            ':range_value' => $range,
            ':top_range' => $topRange,
            ':lower_range' => $lowerRange,
            ':ptr' => $ptr,
            ':plr' => $plr,
            ':api_server_time' => $serverTime,
            ':collected_at' => $collectedAt,
        ]);

        $inserted++;

    } catch (PDOException $e) {
        $skipped++;
    }
}

finishCollector(
    true,
    "Collection finished. {$inserted} records inserted, {$skipped} skipped."
);


/*
|--------------------------------------------------------------------------
| FINISH FUNCTION
|--------------------------------------------------------------------------
*/

function finishCollector(bool $success, string $message): void
{
    global $isCli;

    if ($isCli) {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        exit($success ? 0 : 1);
    }

    $_SESSION['collector_message'] = $message;
    $_SESSION['collector_success'] = $success;

    header('Location: admin/dashboard.php');
    exit;
}