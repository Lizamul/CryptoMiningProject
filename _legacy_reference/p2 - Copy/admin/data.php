<?php

require_once __DIR__ . '/../auth.php';

requireRole('admin');

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
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>All Data</title>

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
            Admin:
            <strong>
                <?= htmlspecialchars($_SESSION['username']) ?>
            </strong>
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a
                href="data.php"
                class="active"
            >
                All Data
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </nav>

    </aside>


    <main class="content">

        <div class="page-header">

            <div>

                <h1>All Collected Data</h1>

                <p class="muted">

                    <?= number_format($totalRows) ?>
                    records found

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
                    placeholder="Search btc_idr, ETH, etc."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button
                    class="btn"
                    type="submit"
                >
                    Search
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
                        <th>Symbol</th>

                        <th>High</th>
                        <th>Low</th>
                        <th>Current</th>

                        <th>Range</th>
                        <th>TR</th>
                        <th>LR</th>

                        <th>PTR</th>
                        <th>PLR</th>

                        <th>API Time</th>
                        <th>Collected At</th>

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
                                No data found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div class="pagination">

                <?php if ($page > 1): ?>

                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                        ← Previous
                    </a>

                <?php endif; ?>


                <span>
                    Page <?= $page ?>
                    of <?= $totalPages ?>
                </span>


                <?php if ($page < $totalPages): ?>

                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                        Next →
                    </a>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>
</html>