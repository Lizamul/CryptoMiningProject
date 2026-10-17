<?php

require_once __DIR__ . '/../auth.php';

requireRole('admin');

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$totalRecords = $pdo
    ->query("SELECT COUNT(*) FROM patterned_dataset")
    ->fetchColumn();

$totalPairs = $pdo
    ->query("SELECT COUNT(DISTINCT pair) FROM patterned_dataset")
    ->fetchColumn();

$lastCollection = $pdo
    ->query("SELECT MAX(collected_at) FROM patterned_dataset")
    ->fetchColumn();

/*
|--------------------------------------------------------------------------
| BTC DATA FOR GRAPH
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

$btcRows = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Reverse so graph is oldest → newest
|--------------------------------------------------------------------------
*/

$btcRows = array_reverse($btcRows);

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
| COLLECTOR MESSAGE
|--------------------------------------------------------------------------
*/

$collectorMessage =
    $_SESSION['collector_message']
    ?? null;

$collectorSuccess =
    $_SESSION['collector_success']
    ?? null;

unset(
    $_SESSION['collector_message'],
    $_SESSION['collector_success']
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

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
            Admin:
            <strong>
                <?= htmlspecialchars($_SESSION['username']) ?>
            </strong>
        </div>

        <nav>

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>

            <a href="data.php">
                Data
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </nav>

    </aside>


    <main class="content">

        <div class="page-header">

            <div>

                <h1>Admin Dashboard</h1>

                <p class="muted">
                    Cryptocurrency patterned dataset control panel
                </p>

            </div>


            <form
                action="../collector.php"
                method="POST"
            >

                <button
                    class="btn"
                    type="submit"
                >
                    Collect Latest Data
                </button>

            </form>

        </div>


        <?php if ($collectorMessage): ?>

            <div class="alert <?= $collectorSuccess ? 'success' : 'error' ?>">

                <?= htmlspecialchars($collectorMessage) ?>

            </div>

        <?php endif; ?>


        <div class="cards">

            <div class="card">

                <span>Total Records</span>

                <strong>
                    <?= number_format($totalRecords) ?>
                </strong>

            </div>


            <div class="card">

                <span>Total Pairs</span>

                <strong>
                    <?= number_format($totalPairs) ?>
                </strong>

            </div>


            <div class="card">

                <span>Last Collection</span>

                <strong class="small-value">

                    <?= $lastCollection
                        ? htmlspecialchars($lastCollection)
                        : 'No data'
                    ?>

                </strong>

            </div>

        </div>


        <section class="panel">

            <h2>BTC/IDR Price</h2>

            <div class="chart-container">

                <canvas id="priceChart"></canvas>

            </div>

        </section>


        <section class="panel">

            <h2>BTC/IDR PTR & PLR</h2>

            <div class="chart-container">

                <canvas id="percentageChart"></canvas>

            </div>

        </section>

    </main>

</div>


<script>

const labels = <?= json_encode($labels) ?>;

const priceData = <?= json_encode($prices) ?>;

const ptrData = <?= json_encode($ptrValues) ?>;

const plrData = <?= json_encode($plrValues) ?>;


/*
|--------------------------------------------------------------------------
| BTC PRICE
|--------------------------------------------------------------------------
*/

new Chart(
    document.getElementById('priceChart'),
    {
        type: 'line',

        data: {
            labels: labels,

            datasets: [
                {
                    label: 'BTC/IDR Price',
                    data: priceData,
                    tension: 0.2
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false
            },

            scales: {

                y: {

                    ticks: {

                        callback: function(value) {

                            return 'Rp ' +
                                Number(value).toLocaleString(
                                    'id-ID'
                                );

                        }

                    }

                }

            }

        }
    }
);


/*
|--------------------------------------------------------------------------
| PTR / PLR
|--------------------------------------------------------------------------
*/

new Chart(
    document.getElementById('percentageChart'),
    {
        type: 'line',

        data: {
            labels: labels,

            datasets: [
                {
                    label: 'PTR',
                    data: ptrData,
                    tension: 0.2
                },

                {
                    label: 'PLR',
                    data: plrData,
                    tension: 0.2
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false
            },

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