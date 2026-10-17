<?php

require_once __DIR__ . '/../auth.php';

requireRole('client');

/*
|--------------------------------------------------------------------------
| LATEST BTC
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM patterned_dataset
    WHERE pair = 'btc_idr'
    ORDER BY collected_at DESC, id DESC
    LIMIT 1
");

$latest = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| STATUS RAMAH (terjemahan PLR ke bahasa sederhana)
|--------------------------------------------------------------------------
*/

$statusText = null;
$statusClass = 'up';

if ($latest) {

    $plr = (float) $latest['plr'];

    if ($plr >= 50) {
        $statusText = 'Lebih dekat ke titik tertinggi hari ini';
        $statusClass = 'up';
    } else {
        $statusText = 'Lebih dekat ke titik terendah hari ini';
        $statusClass = 'down';
    }
}

/*
|--------------------------------------------------------------------------
| BTC HISTORY
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        collected_at,
        current_price,
        ptr,
        plr
    FROM patterned_dataset
    WHERE pair = 'btc_idr'
    ORDER BY collected_at DESC
    LIMIT 100
");

$btcRows = array_reverse(
    $stmt->fetchAll()
);

$labels = [];
$prices = [];
$ptrValues = [];
$plrValues = [];

foreach ($btcRows as $row) {

    $labels[] = date(
        'H:i:s',
        strtotime($row['collected_at'])
    );

    $prices[] = (float) $row['current_price'];

    $ptrValues[] = (float) $row['ptr'];

    $plrValues[] = (float) $row['plr'];
}

/*
|--------------------------------------------------------------------------
| SAPAAN BERDASARKAN WAKTU
|--------------------------------------------------------------------------
*/

$hour = (int) date('H');

if ($hour < 11) {
    $greeting = 'Selamat pagi';
} elseif ($hour < 15) {
    $greeting = 'Selamat siang';
} elseif ($hour < 18) {
    $greeting = 'Selamat sore';
} else {
    $greeting = 'Selamat malam';
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Client</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Quicksand:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/style.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">
            Dukun Crypto
        </div>

        <div class="user-info">
            Halo,
            <strong>
                <?= htmlspecialchars($_SESSION['username']) ?>
            </strong>
            👋
        </div>

        <nav>

            <a
                href="dashboard.php"
                class="active"
            >
                Beranda
            </a>

            <a href="data.php">
                Data
            </a>

            <a href="../logout.php">
                Keluar
            </a>

        </nav>

    </aside>


    <main class="content">

        <div class="page-header">

            <div>

                <h1><?= $greeting ?>, <?= htmlspecialchars($_SESSION['username']) ?></h1>

                <p class="muted">
                    Berikut kondisi BTC/IDR terbaru untuk Anda
                </p>

            </div>

        </div>


        <?php if ($latest): ?>

            <div class="hero-card">

                <div class="hero-top">

                    <div>

                        <div class="hero-label">
                            Harga BTC/IDR saat ini
                        </div>

                        <div class="hero-price">

                            Rp
                            <?= number_format(
                                (float)$latest['current_price'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </div>

                    </div>

                    <span class="hero-status <?= $statusClass ?>">
                        <?= htmlspecialchars($statusText) ?>
                    </span>

                </div>

            </div>


            <div class="cards">

                <div class="card">

                    <span>Rentang Harga Hari Ini</span>

                    <strong>

                        <?= number_format(
                            (float)$latest['range_value'],
                            2
                        ) ?>

                    </strong>

                </div>


                <div class="card">

                    <span>Jarak ke Titik Tertinggi (PTR)</span>

                    <strong>

                        <?= number_format(
                            (float)$latest['ptr'],
                            2
                        ) ?>%

                    </strong>

                </div>


                <div class="card">

                    <span>Jarak ke Titik Terendah (PLR)</span>

                    <strong>

                        <?= number_format(
                            (float)$latest['plr'],
                            2
                        ) ?>%

                    </strong>

                </div>

            </div>

        <?php else: ?>

            <div class="alert error">
                Data BTC/IDR belum tersedia. Silakan cek kembali beberapa saat lagi.
            </div>

        <?php endif; ?>


        <section class="panel">

            <h2>Pergerakan Harga BTC/IDR</h2>

            <p class="muted panel-desc">
                Grafik ini menampilkan 100 pembaruan harga terakhir.
            </p>

            <div class="chart-container">

                <canvas id="priceChart"></canvas>

            </div>

        </section>


        <section class="panel">

            <h2>Posisi dalam Rentang Harian</h2>

            <p class="muted panel-desc">
                PTR menunjukkan seberapa dekat harga dengan titik tertinggi, PLR seberapa dekat dengan titik terendah.
            </p>

            <div class="chart-container">

                <canvas id="percentageChart"></canvas>

            </div>

        </section>

    </main>

</div>


<script>

const labels = <?= json_encode($labels) ?>;

const prices = <?= json_encode($prices) ?>;

const ptr = <?= json_encode($ptrValues) ?>;

const plr = <?= json_encode($plrValues) ?>;


new Chart(
    document.getElementById('priceChart'),
    {
        type: 'line',

        data: {

            labels: labels,

            datasets: [
                {
                    label: 'BTC/IDR',
                    data: prices,
                    borderColor: '#0E7C66',
                    backgroundColor: 'rgba(14, 124, 102, 0.1)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    ticks: {

                        callback: function(value) {

                            return 'Rp ' +
                                Number(value)
                                    .toLocaleString('id-ID');

                        }

                    }

                }

            }

        }
    }
);


new Chart(
    document.getElementById('percentageChart'),
    {
        type: 'line',

        data: {

            labels: labels,

            datasets: [
                {
                    label: 'PTR',
                    data: ptr,
                    borderColor: '#F2994A',
                    tension: 0.3
                },

                {
                    label: 'PLR',
                    data: plr,
                    borderColor: '#0E7C66',
                    tension: 0.3
                }
            ]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {
                    min: 0,
                    max: 100,

                    ticks: {

                        callback: function(value) {
                            return value + '%';
                        }

                    }
                }

            }

        }
    }
);

</script>

</body>
</html>