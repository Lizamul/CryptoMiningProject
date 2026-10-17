<?php

require_once __DIR__ . '/../auth.php';

requireRole('client');

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 50;

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$where = '';

$params = [];

if ($search !== '') {

    $where = "
        WHERE pair LIKE :search
        OR symbol LIKE :search
    ";

    $params[':search'] = '%' . $search . '%';
}

/*
|--------------------------------------------------------------------------
| COUNT
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM patterned_dataset
    $where
";

$countStmt = $pdo->prepare($countSql);

$countStmt->execute($params);

$totalRows = $countStmt->fetchColumn();

$totalPages = max(
    1,
    ceil($totalRows / $perPage)
);

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM patterned_dataset
    $where
    ORDER BY collected_at DESC, id DESC
    LIMIT $perPage
    OFFSET $offset
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$rows = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Riwayat Data</title>

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

            <a href="dashboard.php">
                Beranda
            </a>

            <a
                href="data.php"
                class="active"
            >
                Riwayat Data
            </a>

            <a href="../logout.php">
                Keluar
            </a>

        </nav>

    </aside>


    <main class="content">

        <div class="page-header">

            <div>

                <h1>Riwayat Data</h1>

                <p class="muted">

                    <?= number_format($totalRows) ?>
                    data ditemukan

                </p>

            </div>

        </div>


        <section class="panel">

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Cari btc_idr, ETH, dan lainnya"
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button
                    class="btn"
                    type="submit"
                >
                    Cari
                </button>

                <?php if ($search !== ''): ?>

                    <a
                        href="data.php"
                        class="btn secondary"
                    >
                        Reset
                    </a>

                <?php endif; ?>

            </form>

        </section>


        <section class="panel">

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th>ID</th>
                        <th>Pair</th>
                        <th>Simbol</th>

                        <th>Tertinggi</th>
                        <th>Terendah</th>
                        <th>Saat Ini</th>

                        <th>Rentang</th>
                        <th>TR</th>
                        <th>LR</th>

                        <th>PTR</th>
                        <th>PLR</th>

                        <th>Waktu API</th>
                        <th>Waktu Diambil</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($rows as $row): ?>

                        <tr>

                            <td>
                                <?= $row['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['pair']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['symbol'] ?? '') ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['high_price'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['low_price'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['current_price'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['range_value'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['top_range'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['lower_range'], 2) ?>
                            </td>

                            <td>
                                <?= number_format((float)$row['ptr'], 2) ?>%
                            </td>

                            <td>
                                <?= number_format((float)$row['plr'], 2) ?>%
                            </td>

                            <td>
                                <?= htmlspecialchars($row['api_server_time'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['collected_at']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$rows): ?>

                        <tr>

                            <td
                                colspan="13"
                                class="empty"
                            >
                                Belum ada data yang cocok. Coba kata kunci lain, ya.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div class="pagination">

                <?php if ($page > 1): ?>

                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                        ← Sebelumnya
                    </a>

                <?php endif; ?>


                <span>
                    Halaman <?= $page ?>
                    dari <?= $totalPages ?>
                </span>


                <?php if ($page < $totalPages): ?>

                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                        Berikutnya →
                    </a>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>